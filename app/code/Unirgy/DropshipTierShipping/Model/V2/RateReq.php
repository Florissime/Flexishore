<?php

namespace Unirgy\DropshipTierShipping\Model\V2;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;
use Unirgy\Dropship\Helper\Catalog;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;

class RateReq extends DataObject
{
    /**
     * @var HelperData
     */
    protected $_tsHlp;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Catalog
     */
    protected $_helperCatalog;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    public function __construct(
        HelperData $helperData, 
        ScopeConfigInterface $configScopeConfigInterface, 
        Catalog $helperCatalog, 
        DropshipHelperData $dropshipHelperData,
        array $data = []
    )
    {
        $this->_tsHlp = $helperData;
        $this->_scopeConfig = $configScopeConfigInterface;
        $this->_helperCatalog = $helperCatalog;
        $this->_hlp = $dropshipHelperData;

        parent::__construct($data);
    }

    protected $_cachedRates = [];
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

        $vendor = $this->getVendor();
        $store = $this->getStore();

        $rHlp = $this->_hlp->rHlp();
        $conn = $rHlp->getConnection();

        $extraCond = [];

        $cscId = $this->getCustomerShipClass();
        if (!is_array($cscId)) {
            $cscId = [$cscId];
        }
        $vscId = $this->getVendorShipClass();
        if (!is_array($vscId)) {
            $vscId = [$vscId];
        }
        $cgIds = $this->getCustomerGroupId();

        $catId = $this->getCategoryId();
        if (!is_array($catId)) {
            $catId = [$catId];
        }

        $cscCond = [];
        foreach ($cscId as $_cscId) {
            $cscCond[] = $conn->quoteInto('FIND_IN_SET(?,customer_shipclass_id)',$_cscId);
        }
        $cgCond = [];
        foreach ($cgIds as $_cgId) {
            $cgCond[] = $conn->quoteInto('FIND_IN_SET(?,customer_group_id)',$_cgId);
        }
        if ($this->_scopeConfig->isSetFlag('carriers/udtiership/use_customer_group', ScopeInterface::SCOPE_STORE)) {
            $extraCond[] = '( '.implode(' OR ', $cscCond).' ) AND ('.implode(' OR ', $cgCond).' ) ';
        } else {
            $extraCond[] = '( '.implode(' OR ', $cscCond).' ) ';
        }
        $extraCond['__order'] = [
            $this->_helperCatalog->getCaseSql('customer_shipclass_id', array_flip($cscCond), 9999),
        ];
        if ($this->_scopeConfig->isSetFlag('carriers/udtiership/use_customer_group', ScopeInterface::SCOPE_STORE)) {
            $extraCond['__order'][] = $this->_helperCatalog->getCaseSql('customer_group_id', array_flip($cgCond), 9999);
        }

        if (!$vendor || !$vendor->getData('tiership_use_v2_rates')) {
            $vscCond = [
                $conn->quoteInto('FIND_IN_SET(?,vendor_shipclass_id)','*')
            ];
            foreach ($vscId as $_vscId) {
                $vscCond[] = $conn->quoteInto('FIND_IN_SET(?,vendor_shipclass_id)',$_vscId);
            }
            $extraCond[] = '( '.implode(' OR ', $vscCond).' ) ';
        }

        $catCond = [
            $conn->quoteInto('FIND_IN_SET(?,category_ids)','*')
        ];
        foreach ($catId as $_catId) {
            $catCond[] = $conn->quoteInto('FIND_IN_SET(?,category_ids)',$_catId);
        }
        $extraCond[] = '( '.implode(' OR ', $catCond).' ) ';

        $cacheKey = implode('-', [
            intval($vendor && $vendor->getData('tiership_use_v2_rates')),
            $this->getDeliveryType(),
            serialize($extraCond)
        ]);
        $tierRates = [];

        if (array_key_exists($cacheKey, $this->_cachedRates)) {
            $tierRates = $this->_cachedRates[$cacheKey];
        } else {
            if ($vendor && $vendor->getData('tiership_use_v2_rates')) {
                $tierRates = $tsHlp->getVendorV2Rates($vendor->getId(), $this->getDeliveryType(), $extraCond);
            } else {
                $tierRates = $tsHlp->getV2Rates($this->getDeliveryType(), $extraCond);
            }
            $this->_cachedRates[$cacheKey] = $tierRates;
        }
        if (empty($tierRates)) {
            return false;
        }
        reset($tierRates);
        $tierRate = current($tierRates);

        foreach ($this->getSubkeys() as $sk) {
            $_specData = [];
            $value = $this->getUdmultiRate($sk);
            if ($this->_isRateEmpty($value)) {
                $value = $this->getProductRate($sk);
                if ($value===false) {
                    return false;
                } elseif ($this->_isRateEmpty($value)) {
                    $value = $this->_hlp->formatNumber(@$tierRate[$sk]);
                    if (!$tsHlp->isCtCustomPerCustomerZone($sk, $store)) {
                        $skExtra = $sk.'_extra';
                        $extra = [];
                        if (isset($tierRate[$skExtra])) {
                            $extra = $tierRate[$skExtra];
                            if (!is_array($extra)) {
                                $extra = $this->_hlp->unserialize($extra);
                                if (is_array($extra)) {
                                    usort($extra, [$this, 'sortBySortOrder']);
                                }
                            }
                            if (!is_array($extra)) {
                                $extra = [];
                            }
                        }
                        $surcharge = false;
                        foreach ($extra as $__e) {
                            if (isset($__e['customer_shipclass_id'])) {
                                $_curCSC = $__e['customer_shipclass_id'];
                                if (!is_array($_curCSC)) {
                                    $_curCSC = [$_curCSC];
                                }
                                if (array_intersect($_curCSC,$cscId)) {
                                    $surcharge = $this->_hlp->formatNumber(@$__e['surcharge']);
                                    break;
                                }
                            }
                        }
                        if ($tsHlp->isCtPercentPerCustomerZone($sk, $store) && $surcharge!==false) {
                            $value = $value + $value*$surcharge/100;
                        } elseif ($tsHlp->isCtFixedPerCustomerZone($sk, $store) && $surcharge!==false) {
                            $value = $value + $surcharge;
                        }
                    }
                } else {
                    $_specData['is_product'] = true;
                }
            } else {
                $_specData['is_udmulti'] = true;
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
        $rHlp = $this->_hlp->rHlp();
        $conn = $rHlp->getConnection();
        $value = '';
        if ($this->getProduct()->getUdtiershipUseCustom()) {
            $value = false;
            $cscId = $this->getCustomerShipClass();
            $dt = $this->getDeliveryType();
            if (!is_array($cscId)) {
                $cscId = [$cscId];
            }
            if (!is_array($dt)) {
                $dt = [$dt];
            }
            $extraCond = [];
            $cscCond = [];
            foreach ($cscId as $_cscId) {
                $cscCond[] = $conn->quoteInto('FIND_IN_SET(?,customer_shipclass_id)',$_cscId);
            }
            $extraCond = [
                '( '.implode(' OR ', $cscCond).' ) '
            ];
            $extraCond['__order'] = $this->_helperCatalog->getCaseSql('customer_shipclass_id', array_flip($cscCond), 9999);
            $pRates = $tsHlp->getProductV2Rates($this->getProduct(), $dt, $extraCond);
            foreach ($pRates as $pRate) {
                if (!isset($pRate['delivery_type']) || !isset($pRate['customer_shipclass_id'])) continue;
                $__cscId = $pRate['customer_shipclass_id'];
                if (!is_array($__cscId)) {
                    $__cscId = [$__cscId];
                }
                $__dt = $pRate['delivery_type'];
                if (!is_array($__dt)) {
                    $__dt = [$__dt];
                }
                if (array_intersect($__cscId,$cscId)
                    && array_intersect($__dt,$dt)
                ) {
                    $value = $this->_hlp->formatNumber(@$pRate[$sk]);
                    break;
                }
            }
        }
        return $value;
    }

    public function init($catId, $vscId, $cscId, $cgId)
    {
        $tsHlp = $this->_tsHlp;
        $this->setCategoryId($catId);
        $this->setVendorShipClass($vscId);
        $this->setCustomerShipClass($cscId);
        $this->setCustomerGroupId($cgId);
        return $this;
    }

    public function sortBySortOrder($a, $b)
    {
        if (@$a['sort_order']<@$b['sort_order']) {
            return -1;
        } elseif (@$a['sort_order']>@$b['sort_order']) {
            return 1;
        }
        return 0;
    }

}