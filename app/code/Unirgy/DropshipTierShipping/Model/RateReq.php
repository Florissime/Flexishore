<?php

namespace Unirgy\DropshipTierShipping\Model;

use Magento\Framework\DataObject;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

class RateReq extends DataObject
{
    /**
     * @var HelperData
     */
    protected $_tsHlp;
    protected $_hlp;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        HelperData $helperData,
        array $data = []
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_tsHlp = $helperData;
        parent::__construct($data);
    }

    public function getResult()
    {
        $tsHlp = $this->_tsHlp;
        $rateRes = new RateRes([
            'category_id' => $this->getCategoryId(),
            'vendor_ship_class' => $this->getVendorShipClass(),
            'customer_ship_class' => $this->getCustomerShipClass(),
            'product_id' => $this->getProduct()->getId(),
            'product_name' => $this->getProduct()->getName(),
        ]);
        $dataObj = $this->getDataObject();
        $gDataObj = $this->getGlobalDataObject();
        $k = $this->getKey();
        $bk = $this->getBaseKey();
        $dk = $this->getDefaultKey();
        $store = $this->getStore();
        $fallbackLookup = $tsHlp->getFallbackLookupMethod($store);
        $flVendorBase = $fallbackLookup == Source::FL_VENDOR_BASE;
        $flVendorDefault = $fallbackLookup == Source::FL_VENDOR_DEFAULT;
        $flTier = $fallbackLookup == Source::FL_TIER;
        $keyChain = [
            'vendor_exact'   => [$dataObj, $k[0], 'is_vendor', 'is_exact'],
            'vendor_base'    => [$dataObj, $bk[0], 'is_vendor', 'is_base'],
            'vendor_default' => [$dataObj, $dk[0], 'is_vendor', 'is_default'],
            'global_exact'   => [$gDataObj, $k[1], 'is_global', 'is_exact'],
            'global_base'    => [$gDataObj, $bk[1], 'is_global', 'is_base'],
            'global_default' => [$gDataObj, $dk[1], 'is_global', 'is_default'],
        ];
        $keyOrder = ['vendor_exact','vendor_base','vendor_default','global_exact','global_base','global_default'];
        if ($fallbackLookup == Source::FL_VENDOR_BASE) {
            $keyOrder = ['vendor_exact','vendor_base','global_exact','global_base','vendor_default','global_default'];
        } elseif ($fallbackLookup == Source::FL_TIER) {
            $keyOrder = ['vendor_exact','global_exact','vendor_base','global_base','vendor_default','global_default'];
        }
        foreach ($this->getSubkeys() as $sk) {
            $_specData = [];
            $skipKeys = [];
            if (!$tsHlp->isCtCustomPerCustomerZone($sk, $store)) {
                $skipKeys = ['vendor_exact','global_exact'];
            }
            $value = $this->getUdmultiRate($sk);
            if ($this->_isRateEmpty($value)) {
                $value = $this->getProductRate($sk);
                if ($this->_isRateEmpty($value)) {
                    foreach ($keyOrder as $_ko) {
                        if (in_array($_ko, $skipKeys)) continue;
                        $_kc = $keyChain[$_ko];
                        $value = $_kc[0]->getData($_kc[1].'/'.$sk);
                        if (!$this->_isRateEmpty($value)) {
                            $__kcIdx = 1;
                            while (++$__kcIdx<count($_kc)) {
                                $_specData[$_kc[$__kcIdx]] = true;
                            }
                            break;
                        }
                    }
                    if ($this->_isRateEmpty($value)) {
                        $value = $tsHlp->getFallbackRateValue($sk, $store);
                        $_specData['is_fallback'] = true;
                    }
                } else {
                    $_specData['is_product'] = true;
                }
            } else {
                $_specData['is_udmulti'] = true;
            }
            if ($tsHlp->isCtPercentPerCustomerZone($sk, $store)) {
                $addPcnt = $dataObj->getData($k[0].'/'.$sk);
                if ($this->_isRateEmpty($addPcnt)) {
                    $addPcnt = $gDataObj->getData($k[1].'/'.$sk);
                }
                $value = $value + $value*$addPcnt/100;
            } elseif ($tsHlp->isCtFixedPerCustomerZone($sk, $store)) {
                $addFixed = $dataObj->getData($k[0].'/'.$sk);
                if ($this->_isRateEmpty($addFixed)) {
                    $addFixed = $gDataObj->getData($k[1].'/'.$sk);
                }
                $value = $value + $addFixed;
            }
            $rateRes->setData($sk, $this->_hlp->formatNumber($value));
            $rateRes->setData($rateRes->specPrefix().$sk, $_specData);
        }
        return $rateRes;
    }

    protected function _isRateEmpty($value)
    {
        return null===$value||false===$value||''===$value;
    }

    public function getUdmultiRate($sk)
    {
        $value = '';
        if ($this->getVendor()
            && ($vId = $this->getVendor()->getId())
            && ($mv = $this->getProduct()->getMultiVendorData($vId))
            && $mv['vendor_id'] == $vId
        ) {
            if (!empty($mv['freeshipping'])) {
                $value = 0;
            } elseif (in_array($sk, ['cost'])) {
                if (null !== @$mv['shipping_price'] && '' !== @$mv['shipping_price']) {
                    $value = $this->_hlp->formatNumber(@$mv['shipping_price']);
                }
            }
        }
        return $value;
    }
    public function getProductRate($sk)
    {
        $tsHlp = $this->_tsHlp;
        $value = '';
        if (($prodAttr = $tsHlp->getProductAttribute($sk, $this->getStore()))) {
            $value = $this->getProduct()->getData($prodAttr);
        }
        return $value;
    }

    public function initKey($catId, $vscId, $cscId)
    {
        $tsHlp = $this->_tsHlp;
        $this->setCategoryId($catId);
        $this->setVendorShipClass($vscId);
        $this->setCustomerShipClass($cscId);
        $this->setKey([
            $tsHlp->getRateId([$catId, $cscId]),
            $tsHlp->getRateId([$catId, $vscId, $cscId])
        ]);
        $this->setBaseKey([
            $tsHlp->getRateId([$catId, '0']),
            $tsHlp->getRateId([$catId, $vscId, '0'])
        ]);
        $this->setDefaultKey([
            $tsHlp->getRateId([$catId]),
            $tsHlp->getRateId([$catId])
        ]);
        return $this;
    }

}