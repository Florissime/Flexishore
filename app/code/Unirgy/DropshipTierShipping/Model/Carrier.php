<?php

namespace Unirgy\DropshipTierShipping\Model;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Unirgy\DropshipShippingClass\Helper\Data as DropshipShippingClassHelperData;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;
use Unirgy\Dropship\Helper\Catalog;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;
use Unirgy\Dropship\Helper\Item;
use Unirgy\Dropship\Helper\ProtectedCode;

class Carrier
    extends AbstractCarrier
    implements CarrierInterface
{
    /**
     * @var Item
     */
    protected $_helperItem;

    /**
     * @var ProtectedCode
     */
    protected $_helperProtectedCode;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var DropshipHelperData
     */
    protected $_dropshipHelperData;

    /**
     * @var DropshipShippingClassHelperData
     */
    protected $_dropshipShippingClassHelperData;

    /**
     * @var ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $_rateResultMethodFactory;

    /**
     * @var Collection
     */
    protected $_categoryCollection;

    /**
     * @var Catalog
     */
    protected $_helperCatalog;

    public function __construct(ScopeConfigInterface $scopeConfig, 
        ErrorFactory $rateErrorFactory, 
        LoggerInterface $logger, 
        Item $helperItem, 
        ProtectedCode $helperProtectedCode, 
        HelperData $helperData, 
        DropshipHelperData $dropshipHelperData, 
        DropshipShippingClassHelperData $dropshipShippingClassHelperData, 
        ResultFactory $rateResultFactory, 
        MethodFactory $rateResultMethodFactory, 
        Collection $categoryCollection, 
        Catalog $helperCatalog, 
        array $data = [])
    {
        $this->_helperItem = $helperItem;
        $this->_helperProtectedCode = $helperProtectedCode;
        $this->_helperData = $helperData;
        $this->_dropshipHelperData = $dropshipHelperData;
        $this->_dropshipShippingClassHelperData = $dropshipShippingClassHelperData;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateResultMethodFactory = $rateResultMethodFactory;
        $this->_categoryCollection = $categoryCollection;
        $this->_helperCatalog = $helperCatalog;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    protected $_code = 'udtiership';

    public function getItemCalculationQty($item)
    {
        $qty = $item->getTotalQty();
        $address = $this->_helperItem->getAddress($item);
        if ($item->getFreeShipping() === true
            || $address instanceof DataObject && $address->getFreeShipping() === true
        ) {
            $qty = 0;
        } elseif ($item->getFreeShipping()) {
            $qty = max(0,$qty-$item->getFreeShipping());
        }
        return $qty;
    }

    public function collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        if ($this->getConfigFlag('use_simple_rates')) {
            return $this->_collectSimpleRates($request);
        } else {
            return $this->_collectRates($request);
        }
    }

    protected function _collectSimpleRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        $items = $request->getAllItems();
        $hlpd = $this->_helperProtectedCode;
        $tsHlp = $this->_helperData;
        $quote = $this->_helperItem->getQuote($items);
        $address = $this->_helperItem->getAddress($items);
        $cscId = $this->_dropshipShippingClassHelperData->getCustomerShipClass($address);

        $vId = $request->getVendorId();
        $store = $quote->getStore();
        $tierRates = $vId ? $this->getTiershipSimpleRates($vId) : [];
        $vendor = $vId ? $this->_dropshipHelperData->getVendor($vId) : new DataObject();
        $globalTierRates = $this->getGlobalTierShipConfigSimple();
        $costRate = !$this->_isRateEmpty(@$tierRates[$cscId]['cost'])
            ? @$tierRates[$cscId]['cost'] : @$globalTierRates[$cscId]['cost'];
        $additionalRate = !$this->_isRateEmpty(@$tierRates[$cscId]['additional'])
            ? @$tierRates[$cscId]['additional'] : @$globalTierRates[$cscId]['additional'];
        if ($this->_isRateEmpty($costRate)) {
            $costRate = $tsHlp->getFallbackRateValue('cost', $store);
        }
        if ($this->_isRateEmpty($additionalRate)) {
            $additionalRate = $tsHlp->getFallbackRateValue('additional', $store);
        }
        $total = 0;
        $costUsed = false;
        $costUsedByPid = [];
        $costProdAttr = $tsHlp->getProductAttribute('cost', $this->getStore());
        $additionalProdAttr = $tsHlp->getProductAttribute('additional', $this->getStore());
        foreach ($request->getAllItems() as $item) {
            if ($item->getParentItem()) continue;
            $product = $item->getProduct();
            $pId = $product->getId();
            $_qty = $this->getItemCalculationQty($item);
            $pCost = $pAdditional = null;
            if ($costProdAttr) $pCost = $product->getData($costProdAttr);
            if ($additionalProdAttr) $pAdditional = $product->getData($additionalProdAttr);
            if (!$this->_isRateEmpty($pCost)) {
                if ($_qty>0 && empty($costUsedByPid[$pId])) {
                    $costUsedByPid[$pId] = true;
                    $total += $pCost;
                    $_qty--;
                }
            } elseif (!$costUsed) {
                if ($_qty>0) {
                    $costUsed = true;
                    $total += $costRate;
                    $_qty--;
                }
            }
            if (!$this->_isRateEmpty($pAdditional)) {
                $total += $pAdditional*$_qty;
            } else {
                $total += $additionalRate*$_qty;
            }
        }

        $result = $this->_rateResultFactory->create();
        $method = $this->_rateResultMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod('total');
        $method->setMethodTitle($this->getConfigData('name'));

        $price = $this->getFinalPriceWithHandlingFee($total);

        $method->setPrice($price);
        $method->setCost($total);

        $result->append($method);

        return $result;
    }

    protected function _isRateEmpty($value)
    {
        return null===$value||false===$value||''===$value;
    }

    protected function _collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        $items = $request->getAllItems();
        $hlpd = $this->_helperProtectedCode;
        $tsHlp = $this->_helperData;
        $quote = $this->_helperItem->getQuote($items);
        $address = $this->_helperItem->getAddress($items);

        $vscId = $this->_dropshipShippingClassHelperData->getVendorShipClass($request->getVendorId());
        $cscId = $this->_dropshipShippingClassHelperData->getCustomerShipClass($address);

        $vId = $request->getVendorId();
        $store = $quote->getStore();
        $tierRates = $vId ? $this->getTiershipRates($vId) : [];
        $vendor = $vId ? $this->_dropshipHelperData->getVendor($vId) : new DataObject();
        $globalTierRates = $this->getGlobalTierShipConfig();
        $rateReq = new RateReq([
            'data_object' => new DataObject($tierRates),
            'global_data_object' => new DataObject($globalTierRates),
            'store' => $store,
            'vendor' => $vendor
        ]);
        $topCats = $tsHlp->getTopCategories();
        $catIdsToLoad = $catIds = [];
        foreach ($request->getAllItems() as $item) {
            if ($item->getParentItem()) continue;
            $product = $item->getProduct();
            $_catIds = $product->getCategoryIds();
            if (empty($_catIds)) continue;
            reset($_catIds);
            $catIdsToLoad = array_merge($catIdsToLoad, $_catIds);
            $catIds[$item->getId()] = $_catIds;
        }
        $catIdsToLoad = array_unique($catIdsToLoad);
        $iCats = $this->_categoryCollection->addIdFilter($catIdsToLoad);
        $subcatMatchFlag = $this->_configScopeConfigInterface->isSetFlag('carriers/udtiership/match_subcategories', ScopeInterface::SCOPE_STORE);
        $ratesToUse = $ratesByHandling = $ratesByCost = [];
        foreach ($request->getAllItems() as $item) {
            if ($item->getParentItem()) continue;
            $product = $item->getProduct();
            $pId = $product->getId();
            $rateReq->setProduct($product);
            $_rateToUse = false;
            if (!empty($ratesToUse[$pId])) {
                $ratesToUse[$pId]->setItemQty($ratesToUse[$pId]->getItemQty()+$this->getItemCalculationQty($item));
                continue;
            }
            if (!empty($catIds[$item->getId()])) {
                $exactMatched = $subcatMatched = false;
                foreach ($catIds[$item->getId()] as $iCatId) {
                    if (!($iCat = $iCats->getItemById($iCatId))) continue;
                    $_exactMatched = $_subcatMatched = false;
                    if ($topCats) $_exactMatched = $topCats->getItemById($iCatId);
                    $catId = null;
                    if ($_exactMatched) {
                        $catId = $iCatId;
                    } elseif ($subcatMatchFlag) {
                        $_catPath = explode(',', $this->_helperCatalog->getPathInStore($iCat));
                        foreach ($_catPath as $_catPathId) {
                            if ($topCats && $topCats->getItemById($_catPathId)) {
                                $catId = $_catPathId;
                                $_subcatMatched = true;
                                break;
                            }
                        }
                    }
                    if ($catId && $topCats && $topCats->getItemById($catId)
                        && ($_exactMatched || !$exactMatched && !$_rateToUse)
                    ) {
                        $rateReq->initKey($catId, $vscId, $cscId);
                        $rateReq->setSubkeys(['cost', 'additional', 'handling']);
                        $_rateToUse = $rateReq->getResult();
                    }
                    $exactMatched = $exactMatched || $_exactMatched;
                    $subcatMatched = $subcatMatched || $_subcatMatched;
                }
            }
            if ($_rateToUse) {
                $_rateToUse->setData('item_qty', $this->getItemCalculationQty($item));
                $ratesToUse[$pId] = $_rateToUse;
                $groupId = $_rateToUse->getCategoryId();
                if ($_rateToUse->isProductRate('cost')) {
                    $groupId = 'product'.$pId;
                } elseif ($_rateToUse->isFallbackRate('cost')) {
                    $groupId = 'fallback';
                }
                $ratesByCost[$groupId][] = $_rateToUse;
                $hGroupId = $_rateToUse->getCategoryId();
                if ($_rateToUse->isProductRate('handling')) {
                    $hGroupId = 'product'.$pId;
                } elseif ($_rateToUse->isFallbackRate('handling')) {
                    $hGroupId = 'fallback';
                }
                $ratesByHandling[$hGroupId][] = $_rateToUse;
                if (!isset($maxCost) || $maxCost<$_rateToUse->getData('cost')) {
                    $maxCost = $_rateToUse->getData('cost');
                    $maxCostGroupId = $groupId;
                    $maxCostId = $pId;
                }
                if (!isset($maxHandling) || $maxHandling<$_rateToUse->getData('handling')) {
                    $maxHandling = $_rateToUse->getData('handling');
                    $maxHandlingId = $pId;
                    $maxHandlingGroupId = $hGroupId;
                }
            }
        }

        $calculationMethod = $tsHlp->getCalculationMethod($store);

        $totalsByGroup = [];
        $total = 0;
        foreach ($ratesByCost as $groupId => $groupRates) {
            $_total = 0;
            foreach ($groupRates as $rateToUse) {
                $__total = 0;
                $qty = $rateToUse->getItemQty();
                if ($tsHlp->isMaxCalculationMethod($store)
                    && $rateToUse->getProductId()==$maxCostId
                    || $tsHlp->isSumCalculationMethod($store)
                ) {
                    if ($qty>0) {
                        $qty--;
                        $__total += $rateToUse->getCost();
                    }
                }
                if ($tsHlp->isMultiplyCalculationMethod($store)) {
                    $__total += $qty*$rateToUse->getCost();
                } elseif ($tsHlp->useAdditional($store)) {
                    $__total += $qty*$rateToUse->getAdditional();
                }
                $total += $__total;
                $_total += $__total;
            }
            $totalsByGroup[$groupId] = $_total;
        }

        $handling = 0;
        if ($tsHlp->useHandling($store)) {
            if ($tsHlp->useMaxFixedHandling($store)) {
                $handling = $maxHandling;
            } else {
                foreach ($ratesByHandling as $groupId => $groupRates) {
                    $_handling = 0;
                    foreach ($groupRates as $rateToUse) {
                        $__total = 0;
                        $qty = $rateToUse->getItemQty();
                        if ($tsHlp->isMaxCalculationMethod($store)
                            && $rateToUse->getProductId()==$maxCostId
                            || $tsHlp->isSumCalculationMethod($store)
                        ) {
                            if ($qty>0) {
                                $qty--;
                                $__total += $rateToUse->getCost();
                            }
                        }
                        if ($tsHlp->isMultiplyCalculationMethod($store)) {
                            $__total += $qty*$rateToUse->getCost();
                        } elseif ($tsHlp->useAdditional($store)) {
                            $__total += $qty*$rateToUse->getAdditional();
                        }
                        if ($tsHlp->usePercentHandling($store)) {
                            $_handling = $__total*$rateToUse->getHandling()/100;
                        } elseif ($tsHlp->useFixedHandling($store)) {
                            if ($rateToUse->getHandling()>$_handling) {
                                $_handling = $rateToUse->getHandling();
                            }
                        }
                    }
                    $handling += $_handling;
                }
            }
        }

        $total += $handling;

        $result = $this->_rateResultFactory->create();
        $method = $this->_rateResultMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod('total');
        $method->setMethodTitle($this->getConfigData('name'));

        $price = $this->getFinalPriceWithHandlingFee($total);

        $method->setPrice($price);
        $method->setCost($total);

        $result->append($method);

        return $result;
    }

    protected function _getRateToUse($tierRates, $globalTierRates, $catId, $vscId, $cscId, $field)
    {
        return $this->_helperData->getRateToUse($tierRates, $globalTierRates, $catId, $vscId, $cscId, $field);
    }

    public function getTiershipRates($vendor)
    {
        return $this->_helperData->getVendorTiershipRates($vendor);
    }

    public function getGlobalTierShipConfig()
    {
        return $this->_helperData->getGlobalTierShipConfig();
    }

    public function getTiershipSimpleRates($vendor)
    {
        return $this->_helperData->getVendorTiershipSimpleRates($vendor);
    }

    public function getGlobalTierShipConfigSimple()
    {
        return $this->_helperData->getGlobalTierShipConfigSimple();
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['total'=>$this->getConfigData('name')];
    }
}