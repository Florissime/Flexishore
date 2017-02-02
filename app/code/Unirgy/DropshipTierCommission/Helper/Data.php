<?php

namespace Unirgy\DropshipTierCommission\Helper;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Locale\Format;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\ScopeInterface;
use Unirgy\Dropship\Helper\Catalog;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Vendor;
use \Zend_Filter_LocalizedToNormalized;

/**
 * Class Data
 * @package Unirgy\DropshipTierCommission\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var Resolver
     */
    protected $_localeResolver;

    /**
     * @var Catalog
     */
    protected $_catalogHelper;

    /**
     * @var CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var Format
     */
    protected $_formatLocale;

    /**
     * Data constructor.
     * @param Context $context
     * @param HelperData $helper
     * @param Resolver $localeResolver
     * @param Catalog $helperCatalog
     * @param CategoryFactory $modelCategoryFactory
     * @param FormatInterface $format
     */
    public function __construct(
        Context $context,
        HelperData $helper,
        Resolver $localeResolver,
        Catalog $helperCatalog,
        CategoryFactory $modelCategoryFactory,
        FormatInterface $format
    ) {
        $this->_hlp = $helper;
        $this->_localeResolver = $localeResolver;
        $this->_catalogHelper = $helperCatalog;
        $this->_categoryFactory = $modelCategoryFactory;
        $this->_formatLocale = $format;

        parent::__construct($context);
    }

    /**
     * @param $key
     * @param null $store
     * @return mixed
     */
    public function getProductAttributeCode($key, $store = null)
    {
        $cfgKey = sprintf('udropship/tiercom/%s_attribute', $key);
        $cfgVal = $this->scopeConfig->getValue($cfgKey, ScopeInterface::SCOPE_STORE, $store);
        return $cfgVal;
    }

    /**
     * @param $key
     * @param null $store
     * @return bool|\Magento\Eav\Model\Entity\Attribute\AbstractAttribute
     */
    public function getProductAttribute($key, $store = null)
    {
        if (($attrCode = $this->getProductAttributeCode($key, $store))) {
            return $this->_hlp->getProductAttribute($attrCode);
        }
        return false;
    }

    /**
     * @param null $store
     * @return bool|\Magento\Eav\Model\Entity\Attribute\AbstractAttribute
     */
    public function getCommProductAttribute($store = null)
    {
        return $this->getProductAttribute('comm', $store);
    }

    /**
     * @param null $store
     * @return bool|\Magento\Eav\Model\Entity\Attribute\AbstractAttribute
     */
    public function getFixedRateProductAttribute($store = null)
    {
        return $this->getProductAttribute('fixed_rate', $store);
    }

    /**
     * @param Vendor $vendor
     * @param bool $serialize
     */
    public function processTiercomRates($vendor, $serialize = false)
    {
        $tiercomRates = $vendor->getData('tiercom_rates');
        if ($serialize) {
            if (is_array($tiercomRates)) {
                $tiercomRates = serialize($tiercomRates);
            }
        } else {
            if (is_string($tiercomRates)) {
                $tiercomRates = unserialize($tiercomRates);
            }
            if (!is_array($tiercomRates)) {
                $tiercomRates = [];
            }
        }
        $vendor->setData('tiercom_rates', $tiercomRates);
    }

    /**
     * @param Vendor $vendor
     * @param bool $serialize
     */
    public function processTiercomFixedRates($vendor, $serialize = false)
    {
        $tiercomRates = $vendor->getData('tiercom_fixed_rates');
        if (is_string($tiercomRates)) {
            $tiercomRates = unserialize($tiercomRates);
        }
        if (!is_array($tiercomRates)) {
            $tiercomRates = [];
        }
        $udtcFixedConfig = $tiercomRates;
        if (is_array($udtcFixedConfig) && !empty($udtcFixedConfig)
            && !empty($udtcFixedConfig['limit']) && is_array($udtcFixedConfig['limit'])
        ) {
            reset($udtcFixedConfig['limit']);
            $firstTitleKey = key($udtcFixedConfig['limit']);
            if (!is_numeric($firstTitleKey)) {
                $newudtcFixedConfig = [];
                $filter = new Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_localeResolver->getLocale()]
                );
                foreach ($udtcFixedConfig['limit'] as $_k => $_t) {
                    if (($_limit = $filter->filter($udtcFixedConfig['limit'][$_k]))
                        && false !== ($_value = $filter->filter($udtcFixedConfig['value'][$_k]))
                    ) {
                        $_limit = is_numeric($_limit) ? $_limit : '*';
                        $_sk = is_numeric($_limit) ? $_limit : '9999999999';
                        $_sk = 'str' . str_pad((string)$_sk, 20, '0', STR_PAD_LEFT);
                        $newudtcFixedConfig[$_sk] = [
                            'limit' => $_limit,
                            'value' => $_value,
                        ];
                    }
                }
                ksort($newudtcFixedConfig);
                $newudtcFixedConfig = array_values($newudtcFixedConfig);
                $tiercomRates = array_values($newudtcFixedConfig);
            }
        }
        if ($serialize) {
            if (is_array($tiercomRates)) {
                $tiercomRates = serialize($tiercomRates);
            }
        } else {
            if (is_string($tiercomRates)) {
                $tiercomRates = unserialize($tiercomRates);
            }
            if (!is_array($tiercomRates)) {
                $tiercomRates = [];
            }
        }
        $vendor->setData('tiercom_fixed_rates', $tiercomRates);
    }

    /**
     * @param $po
     */
    public function processPo($po)
    {
        $this->_processPoCommission($po);
        $this->_processPoTransactionFee($po);
    }

    /**
     * @param $po
     */
    protected function _processPoCommission($po)
    {
        $v = $this->_hlp->getVendor($po->getUdropshipVendor());
        $cFallbackMethod = $this->_hlp->getVendorFallbackField(
            $v, 'tiercom_fallback_lookup', 'udropship/tiercom/fallback_lookup'
        );
        $tierRates = $po->getUdropshipVendor() ? $this->getTiercomRates($po->getUdropshipVendor()) : [];
        $globalTierRates = $this->getGlobalTierComConfig();
        $topCats = $this->getTopCategories();
        $catIdsToLoad = $catIds = [];
        $pIds = [];
        foreach ($po->getAllItems() as $item) {
            if ($item->getOrderItem()->getParentItem()) continue;
            $pIds[] = $item->getProductId();
        }
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $products */
        $products = $this->_hlp->createObj('\Magento\Catalog\Model\ResourceModel\Product\Collection');
        $products->addIdFilter($pIds);
        if (($tcProdAttr = $this->getCommProductAttribute())) {
            $products->addAttributeToSelect($tcProdAttr->getAttributeCode());
        }
        foreach ($po->getAllItems() as $item) {
            $itemId = spl_object_hash($item);
            if ($item->getOrderItem()->getParentItem() || !($product = $products->getItemById($item->getProductId()))) continue;
            $_catIds = $product->getCategoryIds();
            if (empty($_catIds)) continue;
            reset($_catIds);
            $catIdsToLoad = array_merge($catIdsToLoad, $_catIds);
            $catIds[$itemId] = $_catIds;
        }
        $format = $this->_formatLocale;
        $catIdsToLoad = array_unique($catIdsToLoad);
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $iCats */
        $iCats = $this->_hlp->createObj('\Magento\Catalog\Model\ResourceModel\Category\Collection');
        $iCats->addIdFilter($catIdsToLoad);
        $subcatMatchFlag = $this->scopeConfig->isSetFlag('udropship/tiercom/match_subcategories',
                                                          ScopeInterface::SCOPE_STORE);
        $ratesToUse = [];
        foreach ($po->getAllItems() as $item) {
            $itemId = spl_object_hash($item);
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }

            if (($product = $products->getItemById($item->getProductId()))
                && $tcProdAttr
                && '' !== $product->getData($tcProdAttr->getAttributeCode())
                && null !== $product->getData($tcProdAttr->getAttributeCode())
            ) {
                $ratesToUse[$itemId]['value'] = $format->getNumber(
                    $product->getData($tcProdAttr->getAttributeCode())
                );
            } elseif (!empty($catIds[$itemId])) {
                $exactMatched = $subcatMatched = false;
                $isGlobalTier = true;
                foreach ($catIds[$itemId] as $iCatId) {
                    if (!($iCat = $iCats->getItemById($iCatId))) continue;
                    $_exactMatched = $_subcatMatched = false;
                    $_isGlobalTier = true;
                    $_exactMatched = $topCats->getItemById($iCatId);
                    $catId = null;
                    if ($_exactMatched) {
                        $catId = $iCatId;
                    } elseif ($subcatMatchFlag) {
                        $_catPath = explode(',', $this->_catalogHelper->getPathInStore($iCat));
                        foreach ($_catPath as $_catPathId) {
                            if ($topCats->getItemById($_catPathId)) {
                                $catId = $_catPathId;
                                $_subcatMatched = true;
                                break;
                            }
                        }
                    }
                    if ($catId && $topCats->getItemById($catId)) {
                        $_rateToUse = [];
                        if (isset($tierRates[$catId]) && isset($tierRates[$catId]['value']) && $tierRates[$catId]['value'] !== '') {
                            $_rateToUse['value'] = $tierRates[$catId]['value'];
                            $_isGlobalTier = false;
                        } else {
                            $_rateToUse['value'] = @$globalTierRates[$catId]['value'];
                            $_rateToUse['is_global_tier'] = true;
                        }
                        if ($_rateToUse['value'] !== null && $_rateToUse['value'] !== ''
                            && (
                                !$_isGlobalTier && $isGlobalTier
                                || !$_isGlobalTier && ($_exactMatched || !$exactMatched)
                                || $_isGlobalTier && $isGlobalTier && ($_exactMatched || !$exactMatched)
                            )
                        ) {
                            $_rateToUse['value'] = $format->getNumber($_rateToUse['value']);
                            $ratesToUse[$itemId] = $_rateToUse;
                        }
                    }
                    $exactMatched = $exactMatched || $_exactMatched;
                    $subcatMatched = $subcatMatched || $_subcatMatched;
                    $isGlobalTier = $isGlobalTier && $_isGlobalTier;
                }
            }

            if (!isset($ratesToUse[$itemId])
                || !empty($ratesToUse[$itemId]['is_global_tier'])
                && $cFallbackMethod == 'vendor'
                && '' !== $v->getCommissionPercent()
                && null !== $v->getCommissionPercent()
            ) {
                if ('' !== $v->getCommissionPercent()
                    && null !== $v->getCommissionPercent()
                ) {
                    $ratesToUse[$itemId]['value'] = $format->getNumber($v->getCommissionPercent());
                } else {
                    $ratesToUse[$itemId]['value'] = $format->getNumber($this->scopeConfig->getValue('udropship/tiercom/commission_percent',
                                                                                                     ScopeInterface::SCOPE_STORE));
                }
            }
            $item->setCommissionPercent(@$ratesToUse[$itemId]['value']);
        }
        if ('' !== $v->getCommissionPercent()
            && null !== $v->getCommissionPercent()
        ) {
            $poComPercent = $format->getNumber($v->getCommissionPercent());
        } else {
            $poComPercent = $format->getNumber($this->scopeConfig->getValue('udropship/tiercom/commission_percent',
                                                                             ScopeInterface::SCOPE_STORE));
        }
        $po->setCommissionPercent($poComPercent);
    }

    /**
     * @param $po
     */
    protected function _processItemTierTransactionFee($po)
    {
        $v = $this->_hlp->getVendor($po->getUdropshipVendor());
        $tierRates = $po->getUdropshipVendor() ? $this->getTiercomRates($po->getUdropshipVendor()) : [];
        $globalTierRates = $this->getGlobalTierComConfig();
        $topCats = $this->getTopCategories();
        $catIdsToLoad = $catIds = [];
        $pIds = [];
        foreach ($po->getAllItems() as $item) {
            if ($item->getOrderItem()->getParentItem()) continue;
            $pIds[] = $item->getProductId();
        }
        $products = $this->_hlp->createObj('\Magento\Catalog\Model\ResourceModel\Product\Collection')->addIdFilter($pIds);
        if (($tcProdAttr = $this->getFixedRateProductAttribute())) {
            $products->addAttributeToSelect($tcProdAttr->getAttributeCode());
        }
        foreach ($po->getAllItems() as $item) {
            $itemId = spl_object_hash($item);
            if ($item->getOrderItem()->getParentItem() || !($product = $products->getItemById($item->getProductId()))) continue;
            $_catIds = $product->getCategoryIds();
            if (empty($_catIds)) continue;
            reset($_catIds);
            $catIdsToLoad = array_merge($catIdsToLoad, $_catIds);
            $catIds[$itemId] = $_catIds;
        }
        $format = $this->_formatLocale;
        $catIdsToLoad = array_unique($catIdsToLoad);
        $iCats = $this->_hlp->createObj('\Magento\Catalog\Model\ResourceModel\Category\Collection')->addIdFilter($catIdsToLoad);
        $subcatMatchFlag = $this->scopeConfig->isSetFlag('udropship/tiercom/match_subcategories',
                                                          ScopeInterface::SCOPE_STORE);
        $ratesToUse = [];
        foreach ($po->getAllItems() as $item) {
            $itemId = spl_object_hash($item);
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }

            if (($product = $products->getItemById($item->getProductId()))
                && $tcProdAttr
                && '' !== $product->getData($tcProdAttr->getAttributeCode())
                && null !== $product->getData($tcProdAttr->getAttributeCode())
            ) {
                $ratesToUse[$itemId]['fixed'] = $format->getNumber(
                    $product->getData($tcProdAttr->getAttributeCode())
                );
            } elseif (!empty($catIds[$itemId])) {
                $exactMatched = $subcatMatched = false;
                foreach ($catIds[$itemId] as $iCatId) {
                    if (!($iCat = $iCats->getItemById($iCatId))) continue;
                    $_exactMatched = $_subcatMatched = false;
                    $_exactMatched = $topCats->getItemById($iCatId);
                    $catId = null;
                    if ($_exactMatched) {
                        $catId = $iCatId;
                    } elseif ($subcatMatchFlag) {
                        $_catPath = explode(',', $this->_catalogHelper->getPathInStore($iCat));
                        foreach ($_catPath as $_catPathId) {
                            if ($topCats->getItemById($_catPathId)) {
                                $catId = $_catPathId;
                                $_subcatMatched = true;
                                break;
                            }
                        }
                    }
                    if ($catId && $topCats->getItemById($catId)
                        && ($_exactMatched || !$exactMatched)
                    ) {
                        $_rateToUse = [];
                        $_rateToUse['fixed'] = isset($tierRates[$catId]) && isset($tierRates[$catId]['fixed']) && $tierRates[$catId]['fixed'] !== ''
                            ? $tierRates[$catId]['fixed']
                            : @$globalTierRates[$catId]['fixed'];
                        $_rateToUse['fixed'] = $format->getNumber($_rateToUse['fixed']);
                        $ratesToUse[$itemId] = $_rateToUse;
                    }
                    $exactMatched = $exactMatched || $_exactMatched;
                    $subcatMatched = $subcatMatched || $_subcatMatched;
                }
            }

            if (!empty($ratesToUse[$itemId]['fixed'])) {
                $item->setTransactionFee($item->getTransactionFee() + $item->getQty() * $ratesToUse[$itemId]['fixed']);
            }
        }
    }

    /**
     * @param $po
     */
    protected function _processPoTransactionFee($po)
    {
        $poTransFee = 0;
        $vendor = $this->_hlp->getVendor($po->getUdropshipVendor());
        if ($this->isFlatCalculation($vendor)) {
            if ('' != $vendor->getTransactionFee()) {
                $poTransFee = $this->_hlp->formatNumber($vendor->getTransactionFee());
            } else {
                $poTransFee = $this->_hlp->formatNumber($this->scopeConfig->getValue('udropship/tiercom/transaction_fee',
                                                                                                ScopeInterface::SCOPE_STORE));
            }
        }
        foreach ($po->getAllItems() as $item) {
            $item->setTransactionFee(0);
        }
        $this->_processItemTierTransactionFee($po);
        foreach ($po->getAllItems() as $item) {
            if ($item->getOrderItem()->getParentItem()) continue;
            $this->_processItemRuleTransactionFee($item);
        }
        foreach ($po->getAllItems() as $item) {
            if ($item->getOrderItem()->getParentItem()) continue;
            $poTransFee += $item->getTransactionFee();
        }
        $po->setTransactionFee($poTransFee);
    }

    /**
     * @param $item
     * @return int
     */
    protected function _processItemTransactionFee($item)
    {
        return $this->_processItemRuleTransactionFee($item);
    }

    /**
     * @param $item
     * @return int
     */
    protected function _processItemRuleTransactionFee($item)
    {
        $oItem = $item->getOrderItem();
        $vId = $item->getPo()
            ? $item->getPo()->getUdropshipVendor()
            : ($item->getShipment() ? $item->getShipment()->getUdropshipVendor() : false);
        $tierRates = $vId ? $this->getTiercomFixedRates($vId) : [];
        $fixedRule = $vId ? $this->getTiercomFixedRule($vId) : false;
        $globalTierRates = $this->getGlobalTierComFixedConfig();
        $globalFixedRule = $this->getGlobalTierComFixedRule();
        $_tierConfig = $fixedRule && !empty($tierRates) ? $tierRates : $globalTierRates;
        $_tierRule = $fixedRule && !empty($tierRates) ? $fixedRule : $globalFixedRule;
        if (is_array($_tierConfig) && !empty($_tierConfig)) {
            $ruleValue = null;
            $multiQty = false;
            switch ($_tierRule) {
                case 'item_price':
                    $ruleValue = $oItem->getBasePrice() + $oItem->getBaseTaxAmount() / $oItem->getQtyOrdered() - $oItem->getBaseDiscountAmount() / $oItem->getQtyOrdered();
                    $multiQty = true;
                    break;
            }
            if (!is_null($ruleValue)) {
                foreach ($_tierConfig as $hc) {
                    if (!isset($hc['limit']) || !isset($hc['value'])) continue;
                    if (is_numeric($hc['limit']) && $ruleValue <= $hc['limit']
                        || !is_numeric($hc['limit'])
                    ) {
                        $fixedFee = $hc['value'];
                        if ($multiQty) {
                            $fixedFee = $fixedFee * $item->getQty();
                        }
                        break;
                    }
                }
                if (isset($fixedFee)) {
                    return $item->setTransactionFee($item->getTransactionFee() + $fixedFee);
                }
            }
        }
        return 0;
    }

    /**
     * @param $vendor
     * @return array
     */
    public function getTiercomRates($vendor)
    {
        $vendor = $this->_hlp->getVendor($vendor);
        $value = $vendor->getTiercomRates();
        if (is_string($value)) {
            $value = unserialize($value);
        }
        if (!is_array($value)) {
            $value = [];
        }
        return $value;
    }

    /**
     * @param $vendor
     * @return array
     */
    public function getTiercomFixedRates($vendor)
    {
        $vendor = $this->_hlp->getVendor($vendor);
        $value = $vendor->getTiercomFixedRates();
        if (is_string($value)) {
            $value = unserialize($value);
        }
        if (!is_array($value)) {
            $value = [];
        }
        return $value;
    }

    /**
     * @param $vendor
     * @return mixed
     */
    public function getTiercomFixedRule($vendor)
    {
        $vendor = $this->_hlp->getVendor($vendor);
        $value = $vendor->getTiercomFixedRule();
        return $value;
    }

    /**
     * @param $vendor
     * @return bool
     */
    public function isTierCalculation($vendor)
    {
        return $this->_isCalculation('tier', $vendor);
    }

    /**
     * @param $vendor
     * @return bool
     */
    public function isFlatCalculation($vendor)
    {
        return $this->_isCalculation('flat', $vendor);
    }

    /**
     * @param $vendor
     * @return bool
     */
    public function isRuleCalculation($vendor)
    {
        return $this->_isCalculation('rule', $vendor);
    }

    /**
     * @param $type
     * @param $vendor
     * @return bool
     */
    protected function _isCalculation($type, $vendor)
    {
        $vendor = $this->_hlp->getVendor($vendor);
        $cfgValue = $vendor->getTiercomFixedCalcType();
        if ($cfgValue == '') {
            $cfgValue = $this->scopeConfig->getValue('udropship/tiercom/fixed_calculation_type',
                                                      ScopeInterface::SCOPE_STORE);
        }
        return false !== strpos($cfgValue, $type);
    }

    /**
     * @return mixed
     */
    public function getGlobalTierComConfig()
    {
        $value = $this->scopeConfig->getValue('udropship/tiercom/rates', ScopeInterface::SCOPE_STORE);
        if (is_string($value)) {
            $value = unserialize($value);
        }
        return $value;
    }

    /**
     * @return mixed
     */
    public function getGlobalTierComFixedRule()
    {
        return $this->scopeConfig->getValue('udropship/tiercom/fixed_rule',
                                             ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getGlobalTierComFixedConfig()
    {
        $value = $this->scopeConfig->getValue('udropship/tiercom/fixed_rates',
                                               ScopeInterface::SCOPE_STORE);
        if (is_string($value)) {
            $value = unserialize($value);
        }
        return $value;
    }

    /**
     * @var
     */
    protected $_topCats;

    /**
     * @return mixed
     */
    public function getTopCategories()
    {
        if (null === $this->_topCats) {
            $cHlp = $this->_catalogHelper;
            $topCatId = $this->scopeConfig->getValue('udropship/tiercom/tiered_category_parent',
                                                      ScopeInterface::SCOPE_STORE);
            $topCat = $this->_categoryFactory->create()->load($topCatId);
            if (!$topCat->getId()) {
                $topCat = $cHlp->getStoreRootCategory();
            }
            $this->_topCats = $cHlp->getCategoryChildren(
                $topCat
            );
        }
        return $this->_topCats;
    }
}
