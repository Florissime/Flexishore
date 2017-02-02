<?php

namespace Unirgy\DropshipTierShipping\Model\V2;

use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Unirgy\DropshipShippingClass\Helper\Data as DropshipShippingClassHelperData;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;
use Unirgy\DropshipTierShipping\Model\ResourceModel\DeliveryType\Collection;
use Unirgy\DropshipTierShipping\Model\Source;
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
    protected $_iHlp;

    /**
     * @var ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var Collection
     */
    protected $_deliveryTypeCollection;

    /**
     * @var HelperData
     */
    protected $_tsHlp;

    /**
     * @var ProtectedCode
     */
    protected $_hlpPr;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    /**
     * @var DropshipShippingClassHelperData
     */
    protected $_sclassHlp;

    /**
     * @var Catalog
     */
    protected $_helperCatalog;

    /**
     * @var MethodFactory
     */
    protected $_rateResultMethodFactory;

    public function __construct(
        Item $helperItem,
        ResultFactory $rateResultFactory,
        Collection $deliverytypeCollection,
        HelperData $helperData,
        ProtectedCode $helperProtectedCode,
        DropshipHelperData $dropshipHelperData,
        DropshipShippingClassHelperData $dropshipShippingClassHelperData,
        Catalog $helperCatalog,
        MethodFactory $rateResultMethodFactory,
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory, 
        LoggerInterface $logger, 
        array $data = [])
    {
        $this->_iHlp = $helperItem;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_deliveryTypeCollection = $deliverytypeCollection;
        $this->_tsHlp = $helperData;
        $this->_hlpPr = $helperProtectedCode;
        $this->_hlp = $dropshipHelperData;
        $this->_sclassHlp = $dropshipShippingClassHelperData;
        $this->_helperCatalog = $helperCatalog;
        $this->_rateResultMethodFactory = $rateResultMethodFactory;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    protected $_code = 'udtiership';

    public function getItemCalculationQty($item)
    {
        $qty = $item->getTotalQty();
        $address = $this->_iHlp->getAddress($item);
        $freeMethods = explode(',', $this->getConfigData('free_method'));
        if (in_array($this->_currentDeliveryType, $freeMethods)) {
            if ($item->getFreeShipping() === true
                || $address instanceof DataObject && $address->getFreeShipping() === true
            ) {
                $qty = 0;
            } elseif ($item->getFreeShipping()) {
                $qty = max(0, $qty - $item->getFreeShipping());
            }
        }
        return $qty;
    }
    public function getItemCalculationWeight($item)
    {
        $qty = $this->getItemCalculationQty($item);
        return $qty ? $item->getFullRowWeight()/$qty : $qty;
    }
    public function getItemCalculationPrice($item)
    {
        $qty = $this->getItemCalculationQty($item);
        return $qty ? $item->getBaseRowTotal()/$qty : $qty;
    }

    protected $_quote;
    protected $_address;
    protected $_currentDeliveryType;
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        $items = $request->getAllItems();
        $this->_quote = $this->_iHlp->getQuote($items);
        $this->_address = $this->_iHlp->getAddress($items);
        $result = $this->_rateResultFactory->create();
        $deliveryTypes = $this->_deliveryTypeCollection->setDeliverySort()->toOptionHash();
        foreach ($deliveryTypes as $deliveryType=>$deliveryTypeLabel) {
            $this->_currentDeliveryType = $deliveryType;
            if ($this->_tsHlp->isV2SimpleRates()) {
                $method = $this->_getSimpleRate($request, $deliveryType);
            } elseif ($this->_tsHlp->isV2SimpleConditionalRates()) {
                $method = $this->_getSimpleCondRate($request, $deliveryType);
            } else {
                $method = $this->_getRate($request, $deliveryType);
            }
            if ($method) {
                $result->append($method);
            }
            $this->_currentDeliveryType = null;
        }
        $this->_quote = null;
        $this->_address = null;
        return $result;
    }

    protected function _getSimpleRate(RateRequest $request, $deliveryType)
    {
        $items = $request->getAllItems();
        $hlpd = $this->_hlpPr;
        $tsHlp = $this->_tsHlp;
        $rHlp = $this->_hlp->rHlp();
        $conn = $rHlp->getConnection();

        $quote = $this->_iHlp->getQuote($items);
        $address = $this->_iHlp->getAddress($items);

        $cscId = 0;
        $extraCond = [];
        $cscId = (array)$this->_sclassHlp->getAllCustomerShipClass($address);
        $cscCond = [];
        foreach ($cscId as $_cscId) {
            $cscCond[] = $conn->quoteInto('FIND_IN_SET(?,customer_shipclass_id)',$_cscId);
        }
        $cgIds = [$quote->getCustomerGroupId(),'*'];
        $cgCond = [];
        foreach ($cgIds as $_cgId) {
            $cgCond[] = $conn->quoteInto('FIND_IN_SET(?,customer_group_id)',$_cgId);
        }
        if ($this->_scopeConfig->isSetFlag('carriers/udtiership/use_customer_group', ScopeInterface::SCOPE_STORE)) {
            $extraCond = [
                '( '.implode(' OR ', $cscCond).' ) AND ('.implode(' OR ', $cgCond).' ) '
            ];
        } else {
            $extraCond = [
                '( '.implode(' OR ', $cscCond).' ) '
            ];
        }
        $extraCond['__order'] = [
            $this->_helperCatalog->getCaseSql('customer_shipclass_id', array_flip($cscCond), 9999),
        ];
        if ($this->_scopeConfig->isSetFlag('carriers/udtiership/use_customer_group', ScopeInterface::SCOPE_STORE)) {
            $extraCond['__order'][] = $this->_helperCatalog->getCaseSql('customer_group_id', array_flip($cgCond), 9999);
        }

        $vId = $request->getVendorId();
        $vendor = $vId ? $this->_hlp->getVendor($vId) : new DataObject();
        $store = $quote->getStore();

        $tierRates = [];
        if ($vendor && $vendor->getData('tiership_use_v2_rates')) {
            $tierRates = $tsHlp->getVendorV2SimpleRates($vendor->getId(), $deliveryType, $extraCond);
        } else {
            $tierRates = $tsHlp->getV2SimpleRates($deliveryType, $extraCond);
        }
        $hasTierRate = true;
        $costRate = '';
        $additionalRate = '';
        if (empty($tierRates)) {
            $hasTierRate = false;
            $tierRate = [];
        } else {
            reset($tierRates);
            $tierRate = current($tierRates);
            $costRate = $tierRate['cost'];
            $additionalRate = $tierRate['additional'];
        }
        $total = 0;
        $costUsed = false;
        $costUsedByPid = [];
        foreach ($request->getAllItems() as $item) {
            if ($item->getParentItem()) continue;
            $product = $item->getProduct();
            $pId = $product->getId();
            $_qty = $this->getItemCalculationQty($item);
            $pCost = $this->getProductRate($product, 'cost', $cscId, $deliveryType);
            $pAdditional = $this->getProductRate($product, 'additional', $cscId, $deliveryType);
            if ($product->getUdtiershipUseCustom() && $this->_isRateEmpty($pCost)) {
                return false;
            } elseif ($product->getUdtiershipUseCustom() && !$this->_isRateEmpty($pCost)) {
                if ($_qty>0 && empty($costUsedByPid[$pId])) {
                    $costUsedByPid[$pId] = true;
                    $total += $this->_hlp->formatNumber($pCost);
                    $_qty--;
                }
            } elseif (!$hasTierRate) {
                return false;
            } elseif (!$costUsed) {
                if ($_qty>0) {
                    $costUsed = true;
                    $total += $this->_hlp->formatNumber($costRate);
                    $_qty--;
                }
            }
            if (!$this->_isRateEmpty($pAdditional)) {
                $total += $this->_hlp->formatNumber($pAdditional)*$_qty;
            } else {
                $total += $this->_hlp->formatNumber($additionalRate)*$_qty;
            }
        }

        $method = $this->_rateResultMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

        $deliveryTypes = $this->_deliveryTypeCollection->toOptionHash();

        $method->setMethod($deliveryType);
        $method->setMethodTitle($deliveryTypes[$deliveryType]);

        $price = $this->getFinalPriceWithHandlingFee($total);

        $method->setPrice($price);
        $method->setCost($total);

        return $method;
    }

    protected function _getSimpleCondRate(RateRequest $request, $deliveryType)
    {
        $items = $request->getAllItems();
        $hlpd = $this->_hlpPr;
        $tsHlp = $this->_tsHlp;
        $rHlp = $this->_hlp->rHlp();
        $conn = $rHlp->getConnection();

        $quote = $this->_iHlp->getQuote($items);
        $address = $this->_iHlp->getAddress($items);

        $cscId = 0;
        $extraCond = [];
        $cscId = (array)$this->_sclassHlp->getAllCustomerShipClass($address);
        $cscCond = [];
        foreach ($cscId as $_cscId) {
            $cscCond[] = $conn->quoteInto('FIND_IN_SET(?,customer_shipclass_id)',$_cscId);
        }
        $cgIds = [$quote->getCustomerGroupId(),'*'];
        $cgCond = [];
        foreach ($cgIds as $_cgId) {
            $cgCond[] = $conn->quoteInto('FIND_IN_SET(?,customer_group_id)',$_cgId);
        }
        if ($this->_scopeConfig->isSetFlag('carriers/udtiership/use_customer_group', ScopeInterface::SCOPE_STORE)) {
            $extraCond = [
                '( '.implode(' OR ', $cscCond).' ) AND ('.implode(' OR ', $cgCond).' ) '
            ];
        } else {
            $extraCond = [
                '( '.implode(' OR ', $cscCond).' ) '
            ];
        }
        $extraCond['__order'] = [
            $this->_helperCatalog->getCaseSql('customer_shipclass_id', array_flip($cscCond), 9999),
        ];
        if ($this->_scopeConfig->isSetFlag('carriers/udtiership/use_customer_group', ScopeInterface::SCOPE_STORE)) {
            $extraCond['__order'][] = $this->_helperCatalog->getCaseSql('customer_group_id', array_flip($cgCond), 9999);
        }

        $vId = $request->getVendorId();
        $vendor = $vId ? $this->_hlp->getVendor($vId) : new DataObject();
        $store = $quote->getStore();

        $tierRates = [];
        if ($vendor && $vendor->getData('tiership_use_v2_rates')) {
            $tierRates = $tsHlp->getVendorV2SimpleCondRates($vendor->getId(), $deliveryType, $extraCond);
        } else {
            $tierRates = $tsHlp->getV2SimpleCondRates($deliveryType, $extraCond);
        }
        if (empty($tierRates)) {
            return false;
        }
        $totalQty = $totalWeight = $totalValue = 0;
        $total = 0;
        $costUsed = false;
        $costUsedByPid = [];
        foreach ($request->getAllItems() as $item) {
            if ($item->getParentItem()) continue;
            $product = $item->getProduct();
            $pId = $product->getId();
            $_qtyTotal = $item->getTotalQty();
            $_qty = $this->getItemCalculationQty($item);
            $pCost = $this->getProductRate($product, 'cost', $cscId, $deliveryType);
            if (!$this->_isRateEmpty($pCost)) {
                $total += $this->_hlp->formatNumber($pCost)*$_qty;
            } else {
                $totalQty += $_qty;
                $totalWeight += ($_qty&&$_qtyTotal ? $item->getFullRowWeight()/$_qtyTotal*$_qty : 0);
                $totalValue += ($_qty&&$_qtyTotal ? $item->getBaseRowTotal()/$_qtyTotal*$_qty : 0);
            }
        }

        if ($totalQty!=0 || !$request->getAllItems()) {
            $tierRate = $this->_findCondRate($tierRates, [
                Source::SIMPLE_COND_FULLWEIGHT => $totalWeight,
                Source::SIMPLE_COND_SUBTOTAL => $totalValue,
                Source::SIMPLE_COND_TOTALQTY => $totalQty,
            ]);
            if ($tierRate===false) {
                return false;
            }

            $total += $tierRate;
        }

        $method = $this->_rateResultMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

        $deliveryTypes = $this->_deliveryTypeCollection->toOptionHash();

        $method->setMethod($deliveryType);
        $method->setMethodTitle($deliveryTypes[$deliveryType]);

        $price = $this->getFinalPriceWithHandlingFee($total);

        $method->setPrice($price);
        $method->setCost($total);

        return $method;
    }
    protected function _findCondRate($tierRates, $conditions)
    {
        if (!is_array($tierRates)) return false;
        $result = false;
        foreach ($tierRates as $tierRate) {
            $curCondName = @$tierRate['condition_name'];
            if (empty($curCondName) || !array_key_exists($curCondName, $conditions)) continue;
            $condValue = $conditions[$curCondName];
            $curCond = @$tierRate['condition'];
            if (empty($curCond)) continue;
            if (!is_array($curCond)) {
                $curCond = $this->_hlp->unserialize($curCond);
            }
            if (!is_array($curCond)) continue;
            uasort($curCond, [$this, 'sortConditions']);
            foreach ($curCond as $cc) {
                if (!array_key_exists('condition_to', $cc) || !array_key_exists('price', $cc)) continue;
                if ($condValue<=$cc['condition_to']) {
                    $result = $cc['price'];
                    break 2;
                }
            }
        }
        return $result;
    }

    public function getProductRate($product, $sk, $cscId, $dt)
    {
        $tsHlp = $this->_tsHlp;
        $rHlp = $this->_hlp->rHlp();
        $conn = $rHlp->getConnection();
        $value = '';
        if ($product->getUdtiershipUseCustom()) {
            $value = false;
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
            $cgIds = [$this->_quote->getCustomerGroupId(),'*'];
            $cgCond = [];
            foreach ($cgIds as $_cgId) {
                $cgCond[] = $conn->quoteInto('FIND_IN_SET(?,customer_group_id)',$_cgId);
            }
            if ($this->_scopeConfig->isSetFlag('carriers/udtiership/use_customer_group', ScopeInterface::SCOPE_STORE)) {
                $extraCond = [
                    '( '.implode(' OR ', $cscCond).' ) AND ('.implode(' OR ', $cgCond).' ) '
                ];
            } else {
                $extraCond = [
                    '( '.implode(' OR ', $cscCond).' ) '
                ];
            }
            $extraCond['__order'] = [
                $this->_helperCatalog->getCaseSql('customer_shipclass_id', array_flip($cscCond), 9999),
            ];
            if ($this->_scopeConfig->isSetFlag('carriers/udtiership/use_customer_group', ScopeInterface::SCOPE_STORE)) {
                $extraCond['__order'][] = $this->_helperCatalog->getCaseSql('customer_group_id', array_flip($cgCond), 9999);
            }

            $pRates = $tsHlp->getProductV2Rates($product, $dt, $extraCond);
            if (is_array($pRates)) {
                usort($pRates, [$this, 'sortBySortOrder']);
            } else {
                $pRates = [];
            }
            if (!empty($pRates)) {
                reset($pRates);
                $pRate = current($pRates);
                $value = $this->_hlp->formatNumber(@$pRate[$sk]);
            }
        }
        return $value;
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
    public function sortConditions($a, $b)
    {
        if (@$a['sort_order']<@$b['sort_order']) {
            return -1;
        } elseif (@$a['sort_order']>@$b['sort_order']) {
            return 1;
        }
        if (@$a['condition_to']<@$b['condition_to']) {
            return -1;
        } elseif (@$a['condition_to']>@$b['condition_to']) {
            return 1;
        }
        return 0;
    }

    protected function _isRateEmpty($value)
    {
        return null===$value||false===$value||''===$value;
    }

    protected function _getRate(RateRequest $request, $deliveryType)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        $items = $request->getAllItems();
        $hlpd = $this->_hlpPr;
        $tsHlp = $this->_tsHlp;
        $quote = $this->_iHlp->getQuote($items);
        $address = $this->_iHlp->getAddress($items);

        $vscId = $this->_sclassHlp->getAllVendorShipClass($request->getVendorId());
        $cscId = (array)$this->_sclassHlp->getAllCustomerShipClass($address);
        $cgIds = [$quote->getCustomerGroupId(),'*'];

        $vId = $request->getVendorId();
        $store = $quote->getStore();
        $vendor = $vId ? $this->_hlp->getVendor($vId) : new DataObject();
        $globalTierRates = $this->getGlobalTierShipConfig();
        $rateReq = $this->_hlp->createObj('\Unirgy\DropshipTierShipping\Model\V2\RateReq', ['data'=>[
            'store' => $store,
            'vendor' => $vendor,
            'delivery_type' => $deliveryType
        ]]);
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
        $iCats = $this->_hlp->createObj('\Magento\Catalog\Model\ResourceModel\Category\Collection')->addIdFilter($catIdsToLoad);
        $subcatMatchFlag = $this->_scopeConfig->isSetFlag('carriers/udtiership/match_subcategories', ScopeInterface::SCOPE_STORE);
        $ratesToUse = $ratesByHandling = $ratesByCost = [];
        foreach ($request->getAllItems() as $item) {
            if ($item->getParentItem()) continue;
            $product = $item->getProduct();
            $pId = $product->getId();
            $rateReq->setProduct($product);
            $_rateToUse = false;
            if (!empty($ratesToUse[$pId])) {
                $ratesToUse[$pId]->setItemQty($ratesToUse[$pId]->getItemQty()+$this->getItemCalculationQty($item));
                $ratesToUse[$pId]->setTotalQty($ratesToUse[$pId]->getTotalQty()+$item->getTotalQty());
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
                        $rateReq->init($catId, $vscId, $cscId, $cgIds);
                        $rateReq->setSubkeys(['cost', 'additional', 'handling']);
                        $_rateToUse = $rateReq->getResult();
                    }
                    $exactMatched = $exactMatched || $_exactMatched;
                    $subcatMatched = $subcatMatched || $_subcatMatched;
                }
            }
            if ($_rateToUse===false) {
                $rateReq->init('*', $vscId, $cscId, $cgIds);
                $rateReq->setSubkeys(['cost', 'additional', 'handling']);
                $_rateToUse = $rateReq->getResult();
            }
            if ($_rateToUse===false) {
                return false;
            }
            if ($_rateToUse) {
                $_rateToUse->setData('item_qty', $this->getItemCalculationQty($item));
                $_rateToUse->setData('total_qty', $item->getTotalQty());
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
                            $_handling += $__total*$rateToUse->getHandling()/100;
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

        $deliveryTypes = $this->_deliveryTypeCollection->toOptionHash();

        $method->setMethod($deliveryType);
        $method->setMethodTitle($deliveryTypes[$deliveryType]);

        $price = $this->getFinalPriceWithHandlingFee($total);

        $method->setPrice($price);
        $method->setCost($total);

        $result->append($method);

        return $result;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return $this->_deliveryTypeCollection->toOptionHash();
    }
}