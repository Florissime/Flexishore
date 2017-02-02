<?php

namespace Unirgy\DropshipTierShipping\Helper;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\GroupFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Collection;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipTierShipping\Model\Source;
use Unirgy\Dropship\Helper\Catalog;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;

class Data extends AbstractHelper
{
    /**
     * @var GroupFactory
     */
    protected $_customerGroupFactory;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    /**
     * @var Catalog
     */
    protected $_helperCatalog;

    /**
     * @var CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        Context $context,
        GroupFactory $customerGroupFactory,
        DropshipHelperData $dropshipHelperData,
        Catalog $helperCatalog,
        CategoryFactory $modelCategoryFactory, 
        StoreManagerInterface $modelStoreManagerInterface)
    {
        $this->_customerGroupFactory = $customerGroupFactory;
        $this->_hlp = $dropshipHelperData;
        $this->_helperCatalog = $helperCatalog;
        $this->_categoryFactory = $modelCategoryFactory;
        $this->_storeManager = $modelStoreManagerInterface;

        parent::__construct($context);
    }

    protected $_customerGroups;
    public function getCustomerGroups()
    {
        if ($this->_customerGroups===null) {
            $this->_customerGroups = [];
            $collection = $this->_customerGroupFactory->create()->getCollection();
            foreach ($collection as $item) {
                $this->_customerGroups[$item->getId()] = $item->getCustomerGroupCode();
            }
        }
        return $this->_customerGroups;
    }
    public function getV2RateObj($isVendor=false, $isProduct=false)
    {
        return $this->_getV2RateObj(Source::USE_RATES_V2, $isVendor, $isProduct);
    }
    public function getV2SimpleRateObj($isVendor=false, $isProduct=false)
    {
        return $this->_getV2RateObj(Source::USE_RATES_V2_SIMPLE, $isVendor, $isProduct);
    }
    public function getV2SimpleCondRateObj($isVendor=false, $isProduct=false)
    {
        return $this->_getV2RateObj(Source::USE_RATES_V2_SIMPLE_COND, $isVendor, $isProduct);
    }
    protected function _getV2RateObj($type, $isVendor=false, $isProduct=false)
    {
        return $this->_hlp->createObj('\Unirgy\DropshipTierShipping\Model\Rate', ['data'=>['__use_rate_setup'=>$type, '__use_vendor'=>$isVendor, '__use_product'=>$isProduct]]);
    }

    public function getV2Rates($deliveryType, $extraCond=[])
    {
        $useVendor = !empty($extraCond['__use_vendor']);
        unset($extraCond['__use_vendor']);
        $useProduct = !empty($extraCond['__use_product']);
        unset($extraCond['__use_product']);
        $rateObj = $this->getV2RateObj($useVendor, $useProduct);
        $rateObj->setData('__udload_order', @$extraCond['__order']);
        unset($extraCond['__order']);
        return $this->_getV2Rates($rateObj, $deliveryType, $extraCond);
    }
    public function getV2SimpleRates($deliveryType, $extraCond=[])
    {
        $useVendor = !empty($extraCond['__use_vendor']);
        unset($extraCond['__use_vendor']);
        $useProduct = !empty($extraCond['__use_product']);
        unset($extraCond['__use_product']);
        $rateObj = $this->getV2SimpleRateObj($useVendor, $useProduct);
        $rateObj->setData('__udload_order', @$extraCond['__order']);
        unset($extraCond['__order']);
        return $this->_getV2Rates($rateObj, $deliveryType, $extraCond);
    }
    public function getV2SimpleCondRates($deliveryType, $extraCond=[])
    {
        $useVendor = !empty($extraCond['__use_vendor']);
        unset($extraCond['__use_vendor']);
        $useProduct = !empty($extraCond['__use_product']);
        unset($extraCond['__use_product']);
        $rateObj = $this->getV2SimpleCondRateObj($useVendor, $useProduct);
        $rateObj->setData('__udload_order', @$extraCond['__order']);
        unset($extraCond['__order']);
        return $this->_getV2Rates($rateObj, $deliveryType, $extraCond);
    }

    public function getVendorV2Rates($vId, $deliveryType, $extraCond=[])
    {
        if (!is_array($extraCond)) {
            $extraCond = [];
        }
        $extraCond['__use_vendor'] = true;
        $extraCond['vendor_id=?'] = $vId;
        return $this->getV2Rates($deliveryType, $extraCond);
    }
    public function getVendorV2SimpleRates($vId, $deliveryType, $extraCond=[])
    {
        if (!is_array($extraCond)) {
            $extraCond = [];
        }
        $extraCond['__use_vendor'] = true;
        $extraCond['vendor_id=?'] = $vId;
        return $this->getV2SimpleRates($deliveryType, $extraCond);
    }
    public function getVendorV2SimpleCondRates($vId, $deliveryType, $extraCond=[])
    {
        if (!is_array($extraCond)) {
            $extraCond = [];
        }
        $extraCond['__use_vendor'] = true;
        $extraCond['vendor_id=?'] = $vId;
        return $this->getV2SimpleCondRates($deliveryType, $extraCond);
    }

    public function getProductV2Rates($product, $deliveryType, $extraCond=[])
    {
        if (!is_array($extraCond)) {
            $extraCond = [];
        }
        $pId = $product;
        if ($product instanceof Product) {
            $pId = $product->getId();
        } elseif ($product instanceof DataObject) {
            $pId = $product->getProductId();
        } else {
            $pId = (string)$product;
        }
        $extraCond['__use_product'] = true;
        $extraCond['product_id=?'] = $pId;
        return $this->getV2Rates($deliveryType, $extraCond);
    }

    protected function _getV2Rates($rateObj, $deliveryType, $extraCond=[])
    {
        $rHlp = $this->_hlp->rHlp();
        $conn = $rHlp->getConnection();
        $rateTable = $rateObj->getResource()->getMainTable();
        $fieldsData = $rHlp->myPrepareDataForTable($rateTable, [], true);
        $fields = array_keys($fieldsData);
        $extraCondition = [];
        if (!empty($deliveryType)) {
            $extraCondition[] = $conn->quoteInto('delivery_type_id in (?)', $deliveryType);
        }
        if (is_array($extraCond)) {
            foreach ($extraCond as $__ecKey=>$__ecValue) {
                if (is_int($__ecKey)) {
                    if ($__ecValue instanceof \Zend_Db_Expr) {
                        $__ecValue = $__ecValue->__toString();
                    }
                } else {
                    $__ecValue = $conn->quoteInto($__ecKey, $__ecValue);
                }
                $extraCondition[] = $__ecValue;
            }
        }
        $extraCondition = empty($extraCondition) ? '' : '( '.implode(' ) AND ( ', $extraCondition).' )';
        $existing = $rHlp->loadDbColumns($rateObj, true, $fields, $extraCondition);
        uasort($existing, [$this, 'sortBySortOrder']);
        foreach ($existing as $__k=>&$__v) {
            foreach (['cost_extra','additional_extra','handling_extra','condition'] as $__encKey) {
                if (isset($__v[$__encKey]) && !is_array($__v[$__encKey])) {
                    $__encValue = $this->_hlp->unserialize($__v[$__encKey]);
                    if (is_array($__encValue)) {
                        if ($__encKey=='condition') {
                            usort($__encValue, [$this, 'sortConditions']);
                        } else {
                            usort($__encValue, [$this, 'sortBySortOrder']);
                        }
                    }
                    $__v[$__encKey] = $__encValue;
                }
            }
        }
        return $existing;
    }

    public function saveProductV2Rates($product, $value)
    {
        $pId = $product;
        if ($product instanceof Product) {
            $pId = $product->getId();
        } elseif ($product instanceof DataObject) {
            $pId = $product->getProductId();
        } else {
            $pId = (string)$product;
        }
        if (!empty($pId) && is_array($value)) {
            $saveHelper = new DataObject([
                'rate_obj' => $this->getV2RateObj(false, true),
                'existing_data' => $this->getProductV2Rates($pId, null),
                'fields_to_implode' => ['customer_shipclass_id','customer_group_id'],
                'fields_to_encode' => []
            ]);
            $this->_saveProductV2Rates($pId, $value, $saveHelper);
        }
        return $this;
    }

    protected function _saveProductV2Rates($pId, $value, $saveHelper)
    {
        if (!empty($pId) && is_array($value)) {
            unset($value['$ROW']);
            unset($value['$$ROW']);
            $rHlp = $this->_hlp->rHlp();
            $rateObj = $saveHelper->getRateObj();
            $conn = $rHlp->getConnection();
            $rateTable = $rateObj->getResource()->getMainTable();
            $fieldsData = $rHlp->myPrepareDataForTable($rateTable, [], true);
            $fields = array_keys($fieldsData);
            $fieldsDataExId = $fieldsData;
            unset($fieldsDataExId['rate_id']);
            $fieldsExId = array_keys($fieldsDataExId);

            $existing = $saveHelper->getExistingData();
            if (!is_array($existing)) {
                $existing = [];
            }

            $insert = [];
            foreach ($value as $v) {
                $v['product_id'] = $pId;
                if (!empty($v['rate_id'])) {
                    unset($existing[$v['rate_id']]);
                } else {
                    $v['rate_id'] = null;
                }
                $fieldsToImplode = $saveHelper->getFieldsToImplode();
                if (!empty($fieldsToImplode) && is_array($fieldsToImplode)) {
                    foreach ($fieldsToImplode as $__sk) {
                        $v[$__sk] = isset($v[$__sk])&&is_array($v[$__sk]) ? implode(',', $v[$__sk]) : @$v[$__sk];
                    }
                }
                $fieldsToEncode = $saveHelper->getFieldsToEncode();
                if (!empty($fieldsToEncode) && is_array($fieldsToEncode)) {
                    foreach ($fieldsToEncode as $__sk) {
                        if (isset($v[$__sk])&&is_array($v[$__sk])) {
                            unset($v[$__sk]['$ROW']);
                            unset($v[$__sk]['$$ROW']);
                            usort($v[$__sk], [$this, 'sortBySortOrder']);
                            $v[$__sk] = $this->_hlp->serialize($v[$__sk]);
                        }
                    }
                }
                $insert[] = $rHlp->myPrepareDataForTable($rateTable, $v, true);
            }
            if (!empty($existing)) {
                $conn->delete($rateTable, ['rate_id in (?)'=>array_keys($existing)]);
            }
            if (!empty($insert)) {
                $rHlp->multiInsertOnDuplicate($rateTable, $insert, array_combine($fieldsExId,$fieldsExId));
            }
        }
        return $this;
    }

    public function saveVendorV2Rates($vId, $value)
    {
        if (!empty($vId) && is_array($value) && ($dtId = @$value['delivery_type']) && !empty($value[$dtId]) && is_array($value[$dtId])) {
            $saveHelper = new DataObject([
                'rate_obj' => $this->getV2RateObj(),
                'existing_data' => $this->getVendorV2Rates($vId, $dtId),
                'fields_to_implode' => ['customer_shipclass_id','category_ids','customer_group_id'],
                'fields_to_encode' => ['cost_extra','additional_extra','handling_extra']
            ]);
            $this->_saveVendorV2Rates($vId, $value, $saveHelper);
        }
        return $this;
    }
    public function saveVendorV2SimpleRates($vId, $value)
    {
        if (!empty($vId) && is_array($value) && ($dtId = @$value['delivery_type']) && !empty($value[$dtId]) && is_array($value[$dtId])) {
            $saveHelper = new DataObject([
                'rate_obj' => $this->getV2SimpleRateObj(),
                'existing_data' => $this->getVendorV2SimpleRates($vId, $dtId),
                'fields_to_implode' => ['customer_shipclass_id','customer_group_id']
            ]);
            $this->_saveVendorV2Rates($vId, $value, $saveHelper);
        }
        return $this;
    }
    public function saveVendorV2SimpleCondRates($vId, $value)
    {
        if (!empty($vId) && is_array($value) && ($dtId = @$value['delivery_type']) && !empty($value[$dtId]) && is_array($value[$dtId])) {
            $saveHelper = new DataObject([
                'rate_obj' => $this->getV2SimpleCondRateObj(),
                'existing_data' => $this->getVendorV2SimpleCondRates($vId, $dtId),
                'fields_to_implode' => ['customer_shipclass_id','customer_group_id'],
                'fields_to_encode' => ['condition']
            ]);
            $this->_saveVendorV2Rates($vId, $value, $saveHelper);
        }
        return $this;
    }
    protected function _saveVendorV2Rates($vId, $value, $saveHelper)
    {
        if (!empty($vId) && is_array($value) && ($dtId = @$value['delivery_type']) && !empty($value[$dtId]) && is_array($value[$dtId])) {
            $value = $value[$dtId];
            unset($value['$ROW']);
            unset($value['$$ROW']);
            $rHlp = $this->_hlp->rHlp();
            $rateObj = $saveHelper->getRateObj();
            $conn = $rHlp->getConnection();
            $rateTable = $rateObj->getResource()->getMainTable();
            $fieldsData = $rHlp->myPrepareDataForTable($rateTable, [], true);
            $fields = array_keys($fieldsData);
            $fieldsDataExId = $fieldsData;
            unset($fieldsDataExId['rate_id']);
            $fieldsExId = array_keys($fieldsDataExId);

            $existing = $saveHelper->getExistingData();
            if (!is_array($existing)) {
                $existing = [];
            }

            $insert = [];
            foreach ($value as $v) {
                $v['delivery_type_id'] = $dtId;
                $v['vendor_id'] = $vId;
                if (!empty($v['rate_id'])) {
                    unset($existing[$v['rate_id']]);
                } else {
                    $v['rate_id'] = null;
                }
                $fieldsToImplode = $saveHelper->getFieldsToImplode();
                if (!empty($fieldsToImplode) && is_array($fieldsToImplode)) {
                    foreach ($fieldsToImplode as $__sk) {
                        $v[$__sk] = isset($v[$__sk])&&is_array($v[$__sk]) ? implode(',', $v[$__sk]) : @$v[$__sk];
                    }
                }
                $fieldsToEncode = $saveHelper->getFieldsToEncode();
                if (!empty($fieldsToEncode) && is_array($fieldsToEncode)) {
                    foreach ($fieldsToEncode as $__sk) {
                        if (isset($v[$__sk])&&is_array($v[$__sk])) {
                            unset($v[$__sk]['$ROW']);
                            unset($v[$__sk]['$$ROW']);
                            usort($v[$__sk], [$this, 'sortBySortOrder']);
                            $v[$__sk] = $this->_hlp->serialize($v[$__sk]);
                        }
                    }
                }
                $insert[] = $rHlp->myPrepareDataForTable($rateTable, $v, true);
            }
            if (!empty($existing)) {
                $conn->delete($rateTable, ['rate_id in (?)'=>array_keys($existing)]);
            }
            if (!empty($insert)) {
                $rHlp->multiInsertOnDuplicate($rateTable, $insert, array_combine($fieldsExId,$fieldsExId));
            }
        }
        return $this;
    }

    public function saveV2Rates($value)
    {
        if (is_array($value) && ($dtId = @$value['delivery_type']) && !empty($value[$dtId]) && is_array($value[$dtId])) {
            $saveHelper = new DataObject([
                'rate_obj' => $this->getV2RateObj(),
                'existing_data' => $this->getV2Rates($dtId),
                'fields_to_implode' => ['customer_shipclass_id','category_ids','vendor_shipclass_id','customer_group_id'],
                'fields_to_encode' => ['cost_extra','additional_extra','handling_extra']
            ]);
            $this->_saveV2Rates($value, $saveHelper);
        }
        return $this;
    }
    public function saveV2SimpleRates($value)
    {
        if (is_array($value) && ($dtId = @$value['delivery_type']) && !empty($value[$dtId]) && is_array($value[$dtId])) {
            $saveHelper = new DataObject([
                'rate_obj' => $this->getV2SimpleRateObj(),
                'existing_data' => $this->getV2SimpleRates($dtId),
                'fields_to_implode' => ['customer_shipclass_id','customer_group_id']
            ]);
            $this->_saveV2Rates($value, $saveHelper);
        }
        return $this;
    }
    public function saveV2SimpleCondRates($value)
    {
        if (is_array($value) && ($dtId = @$value['delivery_type']) && !empty($value[$dtId]) && is_array($value[$dtId])) {
            $saveHelper = new DataObject([
                'rate_obj' => $this->getV2SimpleCondRateObj(),
                'existing_data' => $this->getV2SimpleCondRates($dtId),
                'fields_to_implode' => ['customer_shipclass_id','customer_group_id'],
                'fields_to_encode' => ['condition']
            ]);
            $this->_saveV2Rates($value, $saveHelper);
        }
        return $this;
    }
    protected function _saveV2Rates($value, $saveHelper)
    {
        if (is_array($value) && ($dtId = @$value['delivery_type']) && !empty($value[$dtId]) && is_array($value[$dtId])) {
            $value = $value[$dtId];
            unset($value['$ROW']);
            unset($value['$$ROW']);
            $rHlp = $this->_hlp->rHlp();
            $rateObj = $saveHelper->getRateObj();
            $conn = $rHlp->getConnection();
            $rateTable = $rateObj->getResource()->getMainTable();
            $fieldsData = $rHlp->myPrepareDataForTable($rateTable, [], true);
            $fields = array_keys($fieldsData);
            $fieldsDataExId = $fieldsData;
            unset($fieldsDataExId['rate_id']);
            $fieldsExId = array_keys($fieldsDataExId);

            $existing = $saveHelper->getExistingData();
            if (!is_array($existing)) {
                $existing = [];
            }

            $insert = [];
            foreach ($value as $v) {
                $v['delivery_type_id'] = $dtId;
                if (!empty($v['rate_id'])) {
                    unset($existing[$v['rate_id']]);
                } else {
                    $v['rate_id'] = null;
                }
                $fieldsToImplode = $saveHelper->getFieldsToImplode();
                if (!empty($fieldsToImplode) && is_array($fieldsToImplode)) {
                    foreach ($fieldsToImplode as $__sk) {
                        $v[$__sk] = isset($v[$__sk])&&is_array($v[$__sk]) ? implode(',', $v[$__sk]) : @$v[$__sk];
                    }
                }
                $fieldsToEncode = $saveHelper->getFieldsToEncode();
                if (!empty($fieldsToEncode) && is_array($fieldsToEncode)) {
                    foreach ($fieldsToEncode as $__sk) {
                        if (isset($v[$__sk]) && is_array($v[$__sk])) {
                            unset($v[$__sk]['$ROW']);
                            unset($v[$__sk]['$$ROW']);
                            usort($v[$__sk], [$this, 'sortBySortOrder']);
                            $v[$__sk] = $this->_hlp->serialize($v[$__sk]);
                        }
                    }
                }
                $insert[] = $rHlp->myPrepareDataForTable($rateTable, $v, true);
            }
            if (!empty($existing)) {
                $conn->delete($rateTable, ['rate_id in (?)'=>array_keys($existing)]);
            }
            if (!empty($insert)) {
                $rHlp->multiInsertOnDuplicate($rateTable, $insert, array_combine($fieldsExId,$fieldsExId));
            }
        }
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

    public function useRatesSetup()
    {
        return $this->scopeConfig->getValue('carriers/udtiership/use_simple_rates', ScopeInterface::SCOPE_STORE);
    }

    public function isV2Rates($value=null)
    {
        $value = $value !== null ? $value : $this->useRatesSetup();
        return in_array($value, [
            Source::USE_RATES_V2,
            Source::USE_RATES_V2_SIMPLE,
            Source::USE_RATES_V2_SIMPLE_COND,
        ]);
    }
    public function isV2SimpleRates($value=null)
    {
        $value = $value !== null ? $value : $this->useRatesSetup();
        return $value == Source::USE_RATES_V2_SIMPLE;
    }
    public function isV2SimpleConditionalRates($value=null)
    {
        $value = $value !== null ? $value : $this->useRatesSetup();
        return $value == Source::USE_RATES_V2_SIMPLE_COND;
    }
    public function processTiershipRates($vendor, $serialize=false)
    {
        $tiershipRates = $vendor->getData('tiership_rates');
        if ($serialize) {
            if (is_array($tiershipRates)) {
                $tiershipRates = $this->_hlp->serialize($tiershipRates);
            }
        } else {
            if (is_string($tiershipRates)) {
                $tiershipRates = $this->_hlp->unserialize($tiershipRates);
            }
            if (!is_array($tiershipRates)) {
                $tiershipRates = [];
            }
        }
        $vendor->setData('tiership_rates', $tiershipRates);
    }
    public function processTiershipSimpleRates($vendor, $serialize=false)
    {
        $tiershipRates = $vendor->getData('tiership_simple_rates');
        if ($serialize) {
            if (is_array($tiershipRates)) {
                $tiershipRates = $this->_hlp->serialize($tiershipRates);
            }
        } else {
            if (is_string($tiershipRates)) {
                $tiershipRates = $this->_hlp->unserialize($tiershipRates);
            }
            if (!is_array($tiershipRates)) {
                $tiershipRates = [];
            }
        }
        $vendor->setData('tiership_simple_rates', $tiershipRates);
    }
    public function getVendorTiershipRates($vendor)
    {
        $vendor = $this->_hlp->getVendor($vendor);
        $value = $vendor->getTiershipRates();
        if (is_string($value)) {
            $value = $this->_hlp->unserialize($value);
        }
        if (!is_array($value)) {
            $value = [];
        }
        return $value;
    }
    public function getVendorTiershipSimpleRates($vendor)
    {
        $vendor = $this->_hlp->getVendor($vendor);
        $value = $vendor->getTiershipSimpleRates();
        if (is_string($value)) {
            $value = $this->_hlp->unserialize($value);
        }
        if (!is_array($value)) {
            $value = [];
        }
        return $value;
    }
    public function getGlobalTierShipConfig()
    {
        $value = $this->scopeConfig->getValue('carriers/udtiership/rates', ScopeInterface::SCOPE_STORE);
        if (is_string($value)) {
            $value = $this->_hlp->unserialize($value);
        }
        return $value;
    }
    public function getGlobalTierShipConfigSimple()
    {
        $value = $this->scopeConfig->getValue('carriers/udtiership/simple_rates', ScopeInterface::SCOPE_STORE);
        if (is_string($value)) {
            $value = $this->_hlp->unserialize($value);
        }
        return $value;
    }
    public function getRateId($path)
    {
        return implode(':', $path);
    }
    public function getRateToUse($tierRates, $globalTierRates, $catId, $vscId, $cscId, $field)
    {
        $_curClassId = $this->getRateId([$catId, $vscId, $cscId]);
        return isset($tierRates[$_curClassId]) && isset($tierRates[$_curClassId][$field]) && $tierRates[$_curClassId][$field] !== ''
            ? $tierRates[$_curClassId][$field]
            : (isset($tierRates[$catId]) && isset($tierRates[$catId][$field]) && $tierRates[$catId][$field] !== ''
                ? $tierRates[$catId][$field]
                : (isset($globalTierRates[$_curClassId]) && isset($globalTierRates[$_curClassId][$field]) && $globalTierRates[$_curClassId][$field] !== ''
                    ? $globalTierRates[$_curClassId][$field]
                    : @$globalTierRates[$catId][$field]
        ));
    }

    protected $_topCats;
    public function getTopCategories()
    {
        if (null === $this->_topCats) {
            $this->_topCats = $this->_hlp->createObj('\Magento\Framework\Data\Collection');
            $cHlp = $this->_helperCatalog;
            $topCatId = $this->scopeConfig->getValue('carriers/udtiership/tiered_category_parent', ScopeInterface::SCOPE_STORE);
            $topCatIds = is_array($topCatId) ? $topCatId : explode(',', $topCatId);
            foreach ($topCatIds as $topCatId) {
                $topCat = $this->_categoryFactory->create()->load($topCatId);
                if (!$topCat->getId()) continue;
                $cats = $cHlp->getCategoryChildren(
                    $topCat
                );
                foreach ($cats as $cat) {
                    try {
                        $this->_topCats->addItem($cat);
                    } catch (\Exception $e) {}
                }
            }
            if (empty($this->_topCats)) {
                $topCat = $cHlp->getStoreRootCategory(0);
                $cats = $cHlp->getCategoryChildren(
                    $topCat
                );
                foreach ($cats as $cat) {
                    try {
                        $this->_topCats->addItem($cat);
                    } catch (\Exception $e) {}
                }
            }
        }
        return $this->_topCats;
    }

    public function getFallbackRateValue($type, $store=null)
    {
        $cfgKey = sprintf('carriers/udtiership/fallback_rate_%s', $type);
        $cfgVal = $this->scopeConfig->getValue($cfgKey, ScopeInterface::SCOPE_STORE, $store);
        return $cfgVal;
    }

    public function isMultiplyCalculationMethod($store=null)
    {
        return $this->_isMultiplyCalculationMethod(
            $this->scopeConfig->getValue('carriers/udtiership/calculation_method', ScopeInterface::SCOPE_STORE, $store)
        );
    }

    protected function _isMultiplyCalculationMethod($calcMethod)
    {
        return in_array($calcMethod, [
            Source::CM_MULTIPLY_FIRST,
        ]);
    }

    public function isSumCalculationMethod($store=null)
    {
        return $this->_isSumCalculationMethod(
            $this->scopeConfig->getValue('carriers/udtiership/calculation_method', ScopeInterface::SCOPE_STORE, $store)
        );
    }

    protected function _isSumCalculationMethod($calcMethod)
    {
        return in_array($calcMethod, [
            Source::CM_SUM_FIRST_ADDITIONAL,
            Source::CM_SUM_FIRST,
        ]);
    }

    public function isMaxCalculationMethod($store=null)
    {
        return $this->_isMaxCalculationMethod(
            $this->scopeConfig->getValue('carriers/udtiership/calculation_method', ScopeInterface::SCOPE_STORE, $store)
        );
    }

    protected function _isMaxCalculationMethod($calcMethod)
    {
        return in_array($calcMethod, [
            Source::CM_MAX_FIRST_ADDITIONAL,
            Source::CM_MAX_FIRST,
        ]);
    }

    public function getCalculationMethod($store=null)
    {
        return $this->scopeConfig->getValue('carriers/udtiership/calculation_method', ScopeInterface::SCOPE_STORE, $store);
    }
    public function getFallbackLookupMethod($store=null)
    {
        return $this->scopeConfig->getValue('carriers/udtiership/fallback_lookup', ScopeInterface::SCOPE_STORE, $store);
    }

    public function useAdditional($store=null)
    {
        return $this->_useAdditional(
            $this->scopeConfig->getValue('carriers/udtiership/calculation_method', ScopeInterface::SCOPE_STORE, $store)
        );
    }

    public function isUseAdditional($calcMethod)
    {
        return $this->_useAdditional($calcMethod);
    }
    protected function _useAdditional($calcMethod)
    {
        return !in_array($calcMethod, [
            Source::CM_MULTIPLY_FIRST,
            Source::CM_SUM_FIRST,
            Source::CM_MAX_FIRST
        ]);
    }

    public function usePercentHandling($store=null)
    {
        return $this->_percentHandling(
            $this->scopeConfig->getValue('carriers/udtiership/handling_apply_method', ScopeInterface::SCOPE_STORE, $store)
        );
    }

    protected function _percentHandling($handling)
    {
        return in_array($handling, [
            'percent',
        ]);
    }

    public function useFixedHandling($store=null)
    {
        return $this->_fixedHandling(
            $this->scopeConfig->getValue('carriers/udtiership/handling_apply_method', ScopeInterface::SCOPE_STORE, $store)
        );
    }

    protected function _fixedHandling($handling)
    {
        return in_array($handling, [
            'fixed',
        ]);
    }

    public function useMaxFixedHandling($store=null)
    {
        return $this->_maxFixedHandling(
            $this->scopeConfig->getValue('carriers/udtiership/handling_apply_method', ScopeInterface::SCOPE_STORE, $store)
        );
    }

    protected function _maxFixedHandling($handling)
    {
        return in_array($handling, [
            'fixed_max',
        ]);
    }

    protected function _maxHandling($handling)
    {
        return in_array($handling, [
            'fixed_max',
        ]);
    }

    public function useHandling($store=null)
    {
        return $this->_useHandling(
            $this->scopeConfig->getValue('carriers/udtiership/handling_apply_method', ScopeInterface::SCOPE_STORE, $store)
        );
    }

    public function isUseHandling($applyMethod)
    {
        return $this->_useHandling($applyMethod);
    }
    protected function _useHandling($applyMethod)
    {
        return !$this->isNoneValue($applyMethod);
    }

    public function isShowPerVendorBaseRate($calcType)
    {
        return $this->_isCtBasePlusZone($calcType);
    }
    public function isCtBasePlusZone($calcType)
    {
        return $this->_isCtBasePlusZone($calcType);
    }
    protected function _isCtBasePlusZone($calcType)
    {
        return in_array($calcType, [
            Source::CT_BASE_PLUS_ZONE_FIXED,
            Source::CT_BASE_PLUS_ZONE_PERCENT,
        ]);
    }
    public function getCalculationType($type, $store=null)
    {
        $cfgKey = sprintf('carriers/udtiership/%s_calculation_type', $type);
        $cfgVal = $this->scopeConfig->getValue($cfgKey, ScopeInterface::SCOPE_STORE, $store);
        return $cfgVal;
    }

    public function isCtCostBasePlusZone($store=null)
    {
        return $this->isCtBasePlusZone(
            $this->getCalculationType('cost', $store)
        );
    }
    public function isCtAdditionalBasePlusZone($store=null)
    {
        return $this->isCtBasePlusZone(
            $this->getCalculationType('additional', $store)
        );
    }
    public function isCtHandlingBasePlusZone($store)
    {
        return $this->isCtBasePlusZone(
            $this->getCalculationType('handling', $store)
        );
    }

    public function isCtCustomPerCustomerZone($type, $store=null)
    {
        return $this->_isCtCustomPerCustomerZone(
            $this->getCalculationType($type, $store)
        );
    }
    protected function _isCtCustomPerCustomerZone($calcType)
    {
        return in_array($calcType, [
            Source::CT_SEPARATE,
        ]);
    }

    public function isCtPercentPerCustomerZone($type, $store=null)
    {
        return $this->_isCtPercentPerCustomerZone(
            $this->getCalculationType($type, $store)
        );
    }
    public function isUseCtPercentPerCustomerZone($calcType)
    {
        return $this->_isCtPercentPerCustomerZone($calcType);
    }
    protected function _isCtPercentPerCustomerZone($calcType)
    {
        return in_array($calcType, [
            Source::CT_BASE_PLUS_ZONE_PERCENT,
        ]);
    }
    public function isCtFixedPerCustomerZone($type, $store=null)
    {
        return $this->_isCtFixedPerCustomerZone(
            $this->getCalculationType($type, $store)
        );
    }
    public function isUseCtFixedPerCustomerZone($calcType)
    {
        return $this->_isCtFixedPerCustomerZone($calcType);
    }
    protected function _isCtFixedPerCustomerZone($calcType)
    {
        return in_array($calcType, [
            Source::CT_BASE_PLUS_ZONE_FIXED,
        ]);
    }

    public function getProductAttribute($key, $store=null)
    {
        $cfgKey = sprintf('carriers/udtiership/rate_%s_attribute', $key);
        $cfgVal = $this->scopeConfig->getValue($cfgKey, ScopeInterface::SCOPE_STORE, $store);
        return $cfgVal;
    }

    public function getApplyMethod($method, $store=null)
    {
        $cfgKey = sprintf('carriers/udtiership/%s_apply_method', $method);
        $cfgVal = $this->scopeConfig->getValue($cfgKey, ScopeInterface::SCOPE_STORE, $store);
        return $cfgVal;
    }

    public function isApplyMethodPercent($method, $store=null)
    {
        return $this->isPercentValue(
            $this->getApplyMethod($method, $store)
        );
    }

    public function isApplyMethodNone($method, $store=null)
    {
        return $this->isNoneValue(
            $this->getApplyMethod($method, $store)
        );
    }

    public function isPercentValue($type)
    {
        return in_array($type, [
            'percent',
        ]);
    }
    public function isNoneValue($type)
    {
        return in_array($type, [
            'none',
        ]);
    }

    public function getVendorEditUrl()
    {
        if ($this->isV2Rates()) {
            return $this->_urlBuilder->getUrl('udtiership/vendor/v2rates');
        } elseif ($this->scopeConfig->isSetFlag('carriers/udtiership/use_simple_rates', ScopeInterface::SCOPE_STORE)) {
            return $this->_urlBuilder->getUrl('udtiership/vendor/simplerates');
        } else {
            return $this->_urlBuilder->getUrl('udtiership/vendor/rates');
        }
    }

}
