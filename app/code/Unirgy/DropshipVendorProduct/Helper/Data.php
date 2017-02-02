<?php

namespace Unirgy\DropshipVendorProduct\Helper;

use Magento\CatalogInventory\Helper\Data as HelperData;
use Magento\CatalogInventory\Model\Stock\ItemFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\Product\ActionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\DesignInterface;
use Magento\ProductAlert\Helper\Data as ProductAlertHelperData;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorProduct\Model\ProductFactory as ModelProductFactory;
use Unirgy\DropshipVendorProduct\Model\ProductStatus;
use Unirgy\Dropship\Helper\Catalog;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;

class Data extends AbstractHelper
{
    /**
     * @var DesignInterface
     */
    protected $_viewDesignInterface;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Collection
     */
    protected $_setCollection;

    /**
     * @var ProductFactory
     */
    protected $_modelProductFactory;

    /**
     * @var ModelProductFactory
     */
    protected $_uProductFactory;

    /**
     * @var Catalog
     */
    protected $_helperCatalog;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    /**
     * @var ItemFactory
     */
    protected $_stockItemFactory;

    /**
     * @var ActionFactory
     */
    protected $_productActionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Config
     */
    protected $_eavConfig;

    /**
     * @var ProductAlertHelperData
     */
    protected $_productAlertHelper;

    /**
     * @var Action
     */
    protected $_productAction;

    protected $inlineTranslation;
    protected $_transportBuilder;

    public function __construct(
        \Unirgy\Dropship\Model\EmailTransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        Context $context,
        DesignInterface $viewDesignInterface, 
        HelperData $helperData,
        Collection $setCollection,
        ProductFactory $modelProductFactory, 
        ModelProductFactory $udprodProductFactory,
        Catalog $helperCatalog, 
        DropshipHelperData $dropshipHelperData, 
        ItemFactory $stockItemFactory, 
        ActionFactory $productActionFactory,
        StoreManagerInterface $modelStoreManagerInterface, 
        Config $modelConfig, 
        ProductAlertHelperData $productAlertHelperData,
        Action $productAction
    )
    {
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_viewDesignInterface = $viewDesignInterface;
        $this->_helperData = $helperData;
        $this->_setCollection = $setCollection;
        $this->_modelProductFactory = $modelProductFactory;
        $this->_uProductFactory = $udprodProductFactory;
        $this->_helperCatalog = $helperCatalog;
        $this->_hlp = $dropshipHelperData;
        $this->_stockItemFactory = $stockItemFactory;
        $this->_productActionFactory = $productActionFactory;
        $this->_storeManager = $modelStoreManagerInterface;
        $this->_eavConfig = $modelConfig;
        $this->_productAlertHelper = $productAlertHelperData;
        $this->_productAction = $productAction;

        parent::__construct($context);
    }

    public function isIE6()
    {
        return preg_match('/MSIE [1-6]\./i', $this->_request->getServer('HTTP_USER_AGENT'));
    }

    public function isIE7()
    {
        return preg_match('/MSIE [1-7]\./i', $this->_request->getServer('HTTP_USER_AGENT'));
    }
    const MAX_QTY_VALUE = 99999999.9999;
    public function isQty($product)
    {
        return $this->_helperData->isQty($product->getTypeId());
    }

    public function getUdprodTemplateSku($vendor)
    {
        $value = $vendor->getUdprodTemplateSku();
        if (is_string($value)) {
            $value = unserialize($value);
        }
        if (!is_array($value)) {
            $value = [];
        }
        return $value;
    }

    public function getGlobalTemplateSkuConfig()
    {
        $value = $this->scopeConfig->getValue('udprod/template_sku/value', ScopeInterface::SCOPE_STORE);
        if (is_string($value)) {
            $value = unserialize($value);
        }
        return $value;
    }

    public function getVendorTypeOfProductConfig($vendor=null)
    {
        if ($vendor==null) {
            $vendor = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendor();
        }
        return $this->_getTypeOfProductConfig($vendor);
    }

    public function getTypeOfProductConfig()
    {
        return $this->_getTypeOfProductConfig(false);
    }
    protected function _getTypeOfProductConfig($vendor)
    {
        $value = $this->scopeConfig->getValue('udprod/general/type_of_product', ScopeInterface::SCOPE_STORE);
        if (is_string($value)) {
            $value = unserialize($value);
        }
        $setIds = $this->_setCollection
            ->setEntityTypeFilter($this->_modelProductFactory->create()->getResource()->getEntityType()->getId())
            ->load()
            ->toOptionHash();
        $config = [];
        if (is_array($value)) {
            foreach ($value as $val) {
                if ($vendor && !$this->isAllowedTypeOfProduct($val['type_of_product'], $vendor)) continue;
                $_setIds = $val['attribute_set'];
                if (is_array($_setIds) && !empty($_setIds)) {
                    $cfg = [
                        'value'=>$val['type_of_product'],
                        'label'=>$val['type_of_product'],
                        'set_ids'=>[]
                    ];
                    foreach ($_setIds as $_setId) {
                        if (!empty($setIds[$_setId])) {
                            $__setId = $_setId.'-'.$val['type_of_product'];
                            $cfg['set_ids']['__'.$__setId] = [
                                'value'=>$__setId,
                                'label'=>$setIds[$_setId],
                                'is_configurable'=>$this->hasTplConfigurableAttributes(null,$__setId),
                                'is_downloadable'=>$this->isAllowedDownloadable(null,$__setId),
                                'is_grouped'=>$this->isAllowedGrouped(null,$__setId),
                                'is_virtual'=>$this->isAllowedVirtual(null,$__setId),
                                'is_simple'=>$this->isAllowedSimple(null,$__setId)
                            ];
                        }
                    }
                    if (!empty($cfg['set_ids'])) {
                        $config[$val['type_of_product']] = $cfg;
                    }
                }
            }
        }
        return $config;
    }

    public function getTplProdBySetId($vendor, $setId=null)
    {
        if (null === $setId) {
            $setId = $this->_request->getParam('set_id');
        }
        if (empty($setId)) {
            throw new \Exception('Type Of Product not specified');
        }
        list($_setId) = explode('-', $setId);
        $prTpl = $this->_uProductFactory->create();
        $vTplSku = $this->getUdprodTemplateSku($vendor);
        if (isset($vTplSku[$setId]) && isset($vTplSku[$setId]['value'])
            && ($pId=$this->_helperCatalog->getPidBySku($vTplSku[$setId]['value']))
        ) {
            $prTpl->load($pId);
        }
        if (!$prTpl->getId() && isset($vTplSku[$_setId]) && isset($vTplSku[$_setId]['value'])
            && ($pId=$this->_helperCatalog->getPidBySku($vTplSku[$_setId]['value']))
        ) {
            $prTpl->load($pId);
        }
        $gTplSku = $this->getGlobalTemplateSkuConfig();
        if (!$prTpl->getId() && isset($gTplSku[$setId]) && isset($gTplSku[$setId]['value'])
            && ($pId=$this->_helperCatalog->getPidBySku($gTplSku[$setId]['value']))
        ) {
            $prTpl->load($pId);
        }

        if (!$prTpl->getId() && isset($gTplSku[$_setId]) && isset($gTplSku[$_setId]['value'])
            && ($pId=$this->_helperCatalog->getPidBySku($gTplSku[$_setId]['value']))
        ) {
            $prTpl->load($pId);
        } else {
            $prTpl->setAttributeSetId($_setId);
        }
        return $prTpl;
    }

    public function prepareTplProd($prTpl)
    {
        $prTpl->getWebsiteIds();
        $prTpl->getCategoryIds();
        $prTpl->setId(null);
        $prTpl->unsetData('entity_id');
        $prTpl->unsetData('sku');
        $prTpl->unsetData('url_key');
        $prTpl->unsetData('created_at');
        $prTpl->unsetData('updated_at');
        $prTpl->unsetData('has_options');
        $prTpl->unsetData('required_options');
        $prTpl->setStockItem(null);
        $prTpl->unsMediaGalleryImages();
        $prTpl->unsMediaGallery();
        $prTpl->resetTypeInstance();
        foreach ([
            '_cache_instance_products',
            '_cache_instance_product_ids',
            '_cache_instance_configurable_attributes',
            '_cache_instance_used_attributes',
            '_cache_instance_used_product_attributes',
            '_cache_instance_used_product_attribute_ids',
        ] as $cfgKey) {
            $prTpl->unsetData($cfgKey);
        }
        return $this;
    }

    public function initProductEdit($config)
    {
        $r = $this->_request;
        $udSess = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');

        $pId         = array_key_exists('id', $config) ? $config['id'] : $r->getParam('id');
        $prTpl       = !empty($config['template_id']) ? $config['template_id'] : null;
        $typeId      = array_key_exists('type_id', $config) ? $config['type_id'] : $r->getParam('type_id');
        $setId       = array_key_exists('set_id', $config) ? $config['set_id'] : $r->getParam('set_id');
        $skipCheck   = !empty($config['skip_check']);
        $skipPrepare = !empty($config['skip_prepare']);
        $vendor      = !empty($config['vendor']) ? $config['vendor'] : $udSess->getVendor();
        $productData = !empty($config['data']) ? $config['data'] : [];

        list($_setId) = explode('-', $setId);

        $vendor = $this->_hlp->getVendor($vendor);

        if (!$vendor->getId()) {
            throw new \Exception('Vendor not specified');
        }

        $product = $this->_uProductFactory->create()->setStoreId(0);
        if ($pId) {
            if (!$skipCheck) $this->checkProduct($pId);
            $product->load($pId);
        }
        if (!$product->getId()) {
            if (null === $prTpl) {
                $prTpl = $this->getTplProdBySetId($vendor, $setId);
            } else {
                $prTpl = $this->_uProductFactory->create()->load($prTpl);
            }
            if ($setId) {
                $prTpl->setUdprodAttributeSetKey($setId);
                $prTpl->setAttributeSetId($_setId);
            }
            $stockItem = $this->_hlp->getStockItem($product);
            if (!$stockItem) {
                $stockItem = $this->_stockItemFactory->create();
            }
            $prTpl->setStockItem($stockItem);
            $tplStockData = $this->_hlp->getStockItem($prTpl)->getData();
            unset($tplStockData['item_id']);
            unset($tplStockData['product_id']);
            if (empty($productData['stock_data'])) {
                $productData['stock_data'] = [];
            }
            $productData['is_in_stock'] = !isset($productData['is_in_stock']) ? 1 : $productData['is_in_stock'];
            $productData['stock_data'] = array_merge($tplStockData, $productData['stock_data']);
            if (!isset($productData['stock_data']['use_config_manage_stock'])) {
                $productData['stock_data']['use_config_manage_stock'] = 1;
            }
            if (isset($productData['stock_data']['qty']) && (float)$productData['stock_data']['qty'] > self::MAX_QTY_VALUE) {
                $productData['stock_data']['qty'] = self::MAX_QTY_VALUE;
            }
            $this->prepareTplProd($prTpl);
            $product->setData($prTpl->getData());
            $product->setData('__tpl_product', $prTpl);
            if (!$product->getAttributeSetId()) {
                $product->setAttributeSetId(
                    $product->getResource()->getEntityType()->getDefaultAttributeSetId()
                );
            }
            if ($typeId) {
                $product->setTypeId($typeId);
            } elseif (!$product->getTypeId()) {
                $product->setTypeId('simple');
            }
            if (!$product->hasData('status')) {
                $product->setData('status', ProductStatus::STATUS_PENDING);
            }
            if (!$product->hasData('visibility')) {
                $product->setData('visibility', Visibility::VISIBILITY_BOTH);
            }
        }
        if (isset($productData['stock_data'])) {
            $qtyAndStockStatusFields = ['qty', 'is_in_stock'];
            foreach ($qtyAndStockStatusFields as $__qssf) {
                if (array_key_exists($__qssf, $productData['stock_data'])) {
                    $productData['quantity_and_stock_status'][$__qssf] = $productData['stock_data'][$__qssf];
                }
            }
        }
        $product->setData('_edit_in_vendor', true);
        $product->setData('_edit_mode', true);
        if (is_array($productData)) {
            if (!$skipPrepare) $this->prepareProductPostData($product, $productData);
            $udmulti = @$productData['udmulti'];
            if (!isset($productData['price']) && is_array($udmulti) && isset($udmulti['vendor_price'])) {
                $productData['price'] = $udmulti['vendor_price'];
            }
            $product->addData($productData);
        }
        if (!$product->getId()) {
            $product->setUdropshipVendor($vendor->getId());
        }
        $product->setStoreId(0);
        $product->getAttributes();
        return $product;
    }

    public function prepareProductPostData($product, &$productData)
    {
        $this->_hlp->getObj('\Unirgy\DropshipVendorProduct\Helper\ProtectedCode')->prepareProductPostData($product, $productData);
        return $this;
    }

    public function processAfterSave($product)
    {
        $hideFields = explode(',', $this->scopeConfig->getValue('udropship/microsite/hide_product_attributes', ScopeInterface::SCOPE_STORE));
        $hideFields[] = 'udropship_vendor';
        //$hideFields[] = 'tier_price';
        $hideFields[] = 'gallery';
        $hideFields[] = 'recurring_profile';
        $hideFields[] = 'media_gallery';
        $hideFields[] = 'updated_at';

        $attrChanged = $product->getData('udprod_attributes_changed');
        if (!is_array($attrChanged)) {
            try {
                $attrChanged = unserialize($attrChanged);
            } catch (\Exception $e) {
                $attrChanged = [];
            }
        }
        if (!is_array($attrChanged)) {
            $attrChanged = [];
        }

        foreach ($product->getAttributes() as $attr) {
            $attrCode = $attr->getAttributeCode();
            if (!$product->getUdprodIsNew() && !$product->getUdprodIsQcNew()
                && !in_array($attrCode, $hideFields)
                && $product->dataHasChangedFor($attrCode)
                && false === strpos($attrCode, 'udprod_')
                && !in_array($attrCode, ['created_at','updated_at'])
            ) {
                $attrChanged[$attrCode] = sprintf('%s [%s]', $attr->getStoreLabel(), $attr->getAttributeCode());
                $this->setNeedToUnpublish($product, 'attribute_changed');
            }
        }

        if (!$this->_hlp->getStockItem($product) || $this->hasDataChanged($this->_hlp->getStockItem($product))) {
            $attrChanged['stock.data'] = __('Stock Data');
            $this->setNeedToUnpublish($product, 'stock_changed');
        }

        if ($product->getUdprodIsNew()) {
            $this->setNeedToUnpublish($product, 'new_product');
        }

        $product->setData('udprod_attributes_changed', serialize($attrChanged));
        $product->getResource()->saveAttribute($product, 'udprod_attributes_changed');
        $product->setData('udprod_attributes_changed', $attrChanged);
    }

    public function hasDataChanged($object)
    {
        if (!$object->getOrigData()) {
            return true;
        }

        $fields = $object->getResource()->getConnection()->describeTable($object->getResource()->getMainTable());
        foreach (array_keys($fields) as $field) {
            if ($object->getOrigData($field) != $object->getData($field)) {
                return true;
            }
        }

        return false;
    }

    public function checkUniqueVendorSku($product, $vendor)
    {
        if ($this->scopeConfig->isSetFlag('udprod/general/unique_vendor_sku', ScopeInterface::SCOPE_STORE)
            && $this->_hlp->isUdmultiActive()
        ) {
            $udmulti = $product->getData('udmulti');
            if (empty($udmulti['vendor_sku'])) {
                throw new \Exception('Vendor SKU is empty');
            } elseif ($this->_helperCatalog->getPidByVendorSku($udmulti['vendor_sku'], $vendor->getId(), $product->getId())) {
                throw new \Exception(__('Vendor SKU "%1" is already used', $udmulti['vendor_sku']));
            }
        }
    }

    public function processNewConfigurable($product, $vendor)
    {
        if ('configurable' == $product->getTypeId()) {
            $cfgAttrs = $this->getTplConfigurableAttributes(
                $vendor,
                $product
            );
            if (is_array($cfgAttrs) && !empty($cfgAttrs)) {
                $cfgPos=0; foreach ($cfgAttrs as $cfgAttr) {
                    $this->_helperCatalog->createCfgAttr($product, $cfgAttr, ++$cfgPos);
                }
            }
        }
    }

    public function processQuickCreate($prod, $isNew)
    {
        if ('configurable' != $prod->getTypeId()) return $this;
        
        $session = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
        $v = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendor();
        $hlp = $this->_hlp;
        $prHlp = $this;
        $newPids = [];
        if ('configurable' == $prod->getTypeId()) {
            $cfgFirstAttrs = $this->getCfgFirstAttributes($prod, $isNew);
            $cfgFirstAttr = $this->getCfgFirstAttribute($prod, $isNew);
            $cfgFirstAttrId = $cfgFirstAttr->getId();
            $cfgFirstAttrCode = $cfgFirstAttr->getAttributeCode();
            $existingPids = $prod->getTypeInstance(true)->getUsedProductIds($prod);
            $quickCreate = $prod->getData('_cfg_attribute/quick_create');
            $cfgPrices = $prod->getData('__cfg_prices');
            $existingQC = $this->getEditSimpleProductData($prod);
            if (is_array($quickCreate)) {
            $allExistingQC = $this->getEditSimpleProductData($prod, true);
            foreach ($quickCreate as $_qcKey => $qc) {
                $cfgFirstAttrKey = '';
                foreach ($cfgFirstAttrs as $__ca) {
                    $__id = $__ca->getAttributeId();
                    $__code = $__ca->getAttributeCode();
                    $cfgFirstAttrKey .= $__id.'-'.$qc[$__code].'-';
                }
                $cfgFirstAttrKey = rtrim($cfgFirstAttrKey, '-');
                if ($_qcKey == '$ROW') continue;
                $pId = @$qc['simple_id'];
                $qcMP = (array)@$qc['udmulti'];
                $qcSD = (array)@$qc['stock_data'];
                unset($qc['udmulti']);
                unset($qc['stock_data']);
                $qc['is_existing'] = @$qc['is_existing'] || $pId;
                if (!$pId && !empty($qc['sku'])) {
                    $pId = $this->_helperCatalog->getPidBySku($qc['sku']);
                }
                if (!$pId && !empty($qcMP['vendor_sku'])) {
                    $pId = $this->_helperCatalog->getPidByVendorSku($qcMP['vendor_sku'], $v->getId());
                }
                if (!empty($qc['is_existing']) && !$pId) continue;
                $superAttrKey = [];
                foreach ($prod->getTypeInstance(true)->getUsedProductAttributes($prod) as $cfgAttr) {
                    $superAttrKey[] = $cfgAttr->getId().'='.@$qc[$cfgAttr->getAttributeCode()];
                }
                $superAttrKey = implode('-', $superAttrKey);
                foreach ($allExistingQC as $eqcPid=>$eqcData) {
                    if ($eqcData['super_attr_key'] == $superAttrKey) {
                        $pId = $eqcPid;
                        $qc['is_existing'] = true;
                        break;
                    }
                }
                if ($pId) {
                    $newPids[] = $pId;
                }
                if (!empty($qc['is_existing']) && $pId && isset($existingQC[$pId])) {
                    $_eqc   = $existingQC[$pId];
                    $_eqcMP = (array)@$_eqc['udmulti'];
                    $_eqcSD = (array)@$_eqc['stock_data'];
                    unset($_eqc['udmulti']);
                    unset($_eqc['stock_data']);
                    $qcNoChanges = true;
                    foreach ($qc as $_k=>$_v) {
                        if ($_v != @$_eqc[$_k]) {
                            $qcNoChanges = false;
                            break;
                        }
                    }
                    foreach ($qcMP as $_k=>$_v) {
                        if ($_v != @$_eqcMP[$_k]) {
                            $qcNoChanges = false;
                            break;
                        }
                    }
                    foreach ($qcSD as $_k=>$_v) {
                        if ($_v != @$_eqcSD[$_k]) {
                            $qcNoChanges = false;
                            break;
                        }
                    }
                    if ($qcNoChanges && !$prod->getData('udprod_cfg_media_changed/'.$cfgFirstAttrKey)) {
                        continue;
                    }
                }
                $qcProdData = [];
                if (!$this->_hlp->isUdmultiActive()) {
                    $qcSD['is_in_stock'] = !isset($qcSD['is_in_stock']) ? 1 : $qcSD['is_in_stock'];
                    $qcProdData['stock_data'] = $qcSD;
                }
                try {
                    if ($pId) {
                        $qcProdData['options'] = [];
                        $qcProdData['has_options'] = false;
                        $qcProdData['required_options'] = false;
                        $qcProd = $prHlp->initProductEdit([
                            'id' => $pId,
                            'type_id' => 'simple',
                            'data' => $qcProdData,
                            'skip_check' => true
                        ]);
                        $qcProd->uclearOptions();
                        $qcProd->setProductOptions([]);
                        $qcProd->setCanSaveCustomOptions(false);
                    } else {
                        $qcProdData['website_ids'] = $prod->getWebsiteIds();
                        $qcProdData['category_ids'] = $prod->getCategoryIds();
                        $qcProdData['options'] = [];
                        $qcProdData['has_options'] = false;
                        $qcProdData['required_options'] = false;
                        $qcProd = $prHlp->initProductEdit([
                            'id' => false,
                            'type_id' => 'simple',
                            'template_id' => $prod->getId(),
                            'data' => $qcProdData,
                        ]);
                        $qcProd->uclearOptions();
                        $qcProd->setProductOptions([]);
                        $qcProd->setCanSaveCustomOptions(false);
                    }

                    if ($prHlp->isMyProduct($qcProd)) {
                        foreach ($this->getQuickCreateAllowedAttributes() as $_k) {
                            if (isset($qc[$_k])) {
                                $qcProd->setData($_k, $qc[$_k]);
                            }
                        }
                    }
                    $autogenerateOptions = [];
                    if (!$this->scopeConfig->isSetFlag('udprod/general/disable_name_check', ScopeInterface::SCOPE_STORE)) {
                        $ufName = $prod->formatUrlKey(@$qc['name']);
                    } else {
                        $ufName = @$qc['name'];
                    }
                    $ufName = trim($ufName);
                    foreach ($this->getConfigurableAttributes($prod, $isNew) as $attribute) {
                        if ($attribute->getAttributeCode()!='name'||$ufName) {
                            $qcProd->setData($attribute->getAttributeCode(), @$qc[$attribute->getAttributeCode()]);
                        }
                        $value = $qcProd->getAttributeText($attribute->getAttributeCode());
                        $autogenerateOptions[] = $value;
                    }
                    if (!$pId) {
                        if (empty($qc['name']) || !$ufName || !empty($qc['name_auto'])) {
                            $autoName = $prod->getName().'-'.implode('-', $autogenerateOptions);
                            if (!$this->scopeConfig->isSetFlag('udprod/general/disable_name_check', ScopeInterface::SCOPE_STORE)) {
                                $autoName = $prod->formatUrlKey($autoName);
                            }
                            $qcProd->setName($autoName);
                        }
                        $qcProd->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
                    }

                    if ($this->_hlp->isUdmultiActive()) {
                        $qcMP['vendor_sku'] = trim(@$qcMP['vendor_sku']);
                        if (!empty($qcMP['vendor_sku_auto'])) {
                            $qcVsku = $qcMP['vendor_sku'] = $this->_helperCatalog->getVendorSkuByPid($prod->getId(), $v->getId()).'-'.implode('-', $autogenerateOptions);
                            $qcVskuIdx = 0;
                            while ($this->_helperCatalog->getPidByVendorSku($qcVsku, $v->getId(), $pId)) {
                                $qcVsku = $qcMP['vendor_sku'].'-'.(++$qcVskuIdx);
                            }
                            $qcMP['vendor_sku'] = $qcVsku;
                        }
                        if ($this->scopeConfig->isSetFlag('udprod/general/unique_vendor_sku', ScopeInterface::SCOPE_STORE)) {
                            if (empty($qcMP['vendor_sku'])) {
                                throw new \Exception('Vendor SKU is empty');
                            } elseif ($this->_helperCatalog->getPidByVendorSku($qcMP['vendor_sku'], $v->getId(), $pId)) {
                                throw new \Exception(__('Vendor SKU "%1" is already used', $qcMP['vendor_sku']));
                            }
                        }
                    }

                    if (!$qcProd->getSku() && $this->scopeConfig->isSetFlag('udprod/general/auto_sku', ScopeInterface::SCOPE_STORE)) {
                        $__skuAuto = $__skuAuto1 = $prod->getSku().'-'.implode('-', $autogenerateOptions);
                        $__skuAutoIdx = 0;
                        while ($this->_helperCatalog->getPidBySku($__skuAuto, $qcProd->getId())) {
                            $__skuAuto = $__skuAuto1.'-'.(++$__skuAutoIdx);
                        }
                        $qcProd->setSku($__skuAuto);
                    }

                    if ($this->_hlp->getObj('\Unirgy\DropshipVendorProduct\Model\Source')->isCfgUploadImagesSimple()) {
                        $this->processQcMediaChange($prod, $qcProd, $isNew);
                    }


                    $qcProd->setData('_allow_use_renamed_image', true);
                    $qcProd->setUdprodIsQcNew(!$qcProd->getId());
                    $qcProd->save();
                    $this->processAfterSave($qcProd);
                    if ($qcProd->getUdprodNeedToUnpublish()) {
                        $this->addUnpublishPids($prod, [$qcProd->getId()]);
                    }

                    if ($this->_hlp->isUdmultiActive()) {
                        $this->_hlp->rHlp()->insertIgnore(
                            'udropship_vendor_product',
                            ['vendor_id'=>$v->getId(), 'product_id'=>$qcProd->getId(),'status'=>$this->_hlp->getObj('Unirgy\DropshipMulti\Helper\Data')->getDefaultMvStatus()]
                        );
                        $udmultiUpdate = $qcMP;
                        $udmultiUpdate['isNewFlag'] = $isNew;
                        $this->_hlp->processDateLocaleToInternal(
                            $udmultiUpdate,
                            ['special_from_date','special_to_date'],
                            $this->_hlp->getDefaultDateFormat()
                        );
                        $this->_hlp->getObj('Unirgy\DropshipMulti\Helper\Data')->saveThisVendorProductsPidKeys(
                            [$qcProd->getId()=>$udmultiUpdate], $v
                        );
                    }
                } catch (\Exception $e) {
                    $this->_hlp->getObj('\Magento\Framework\Message\ManagerInterface')->addError($e->getMessage());
                    continue;
                }
                $newPids[] = $qcProd->getId();
            }
            }
            $delSimplePids = array_diff($existingPids, $newPids);
            $addSimplePids = array_diff($newPids, $existingPids);

            foreach ($addSimplePids as $addSimplePid) {
                $this->_helperCatalog->linkCfgSimple($prod->getId(), $addSimplePid, true);
            }

            if (!empty($addSimplePids)) {
                $this->_addCfgSimplesDescrData($prod, $isNew, $addSimplePids, 'udprod_cfg_simples_added');
            }
            if (!empty($delSimplePids)) {
                $this->_addCfgSimplesDescrData($prod, $isNew, $delSimplePids, 'udprod_cfg_simples_removed');
            }

            $delProd = $this->_modelProductFactory->create();
            foreach ($delSimplePids as $delSimplePid) {
                $this->_helperCatalog->unlinkCfgSimple($prod->getId(), $delSimplePid, true);
                $delProd->setId($delSimplePid)->delete();
            }
            if (!empty($delSimplePids)) {
                $this->setNeedToUnpublish($prod, 'cfg_simple_removed');
            }
            if (!empty($addSimplePids)) {
                $this->setNeedToUnpublish($prod, 'cfg_simple_added');
            }
            $reindexPids = array_merge($newPids, $existingPids);
            $reindexPids[] = $prod->getId();
            $this->processCfgPriceChanges($prod, $reindexPids);
            $this->addReindexPids($prod, $reindexPids);
        }
    }

    protected function _addCfgSimplesDescrData($prod, $isNew, $simplePids, $descrAttr)
    {
        $cfgSimplesDescrDataCol = $this->_modelProductFactory->create()->getCollection()
            ->addAttributeToSelect(['name','sku']);
        foreach ($this->getConfigurableAttributes($prod, $isNew) as $cfgAttr) {
            $cfgSimplesDescrDataCol->addAttributeToSelect($cfgAttr->getAttributeCode());
        }
        $cfgSimplesDescrDataCol->addIdFilter($simplePids);

        $simplesDescrData = [];
        foreach ($cfgSimplesDescrDataCol as $csdProd) {
            $csdSI = $this->_hlp->getStockItem($csdProd);
            $siHlp = $this->_helperData;
            $_descrText = sprintf('id: %s; sku: %s; stock qty: %s; stock status: %s;',
                $csdProd->getId(), $csdProd->getSku(),
                $csdSI->getQty(),
                ($csdSI->getIsInStock() ? __('In Stock') : __('Out of Stock'))
            );
            foreach ($this->getConfigurableAttributes($prod, $isNew) as $cfgAttr) {
                $_descrText .= sprintf('%s [%s]: %s;',
                    $cfgAttr->getStoreLabel(), $cfgAttr->getAttributeCode(),
                    $cfgAttr->getSource()->getOptionText($prod->getData($cfgAttr->getAttributeCode()))
                );
            }
            $simplesDescrData[$csdProd->getId()] = substr($_descrText, 0, -1);
        }
        $exisSimplesDescr = $prod->getData($descrAttr);
        if (!is_array($exisSimplesDescr)) {
            try {
                $exisSimplesDescr = unserialize($exisSimplesDescr);
            } catch (\Exception $e) {
                $exisSimplesDescr = [];
            }
        }
        if (!is_array($exisSimplesDescr)) {
            $exisSimplesDescr = [];
        }
        $exisSimplesDescr = array_merge($exisSimplesDescr, $simplesDescrData);
        $prod->setData($descrAttr, serialize($exisSimplesDescr));
        $prod->getResource()->saveAttribute($prod, $descrAttr);
        $prod->setData($descrAttr, $exisSimplesDescr);
    }

    public function processQcMediaChange($prod, $qcProd, $isNew)
    {
        $cfgFirstAttrs = $this->getCfgFirstAttributes($prod, $isNew);
        $cfgFirstAttrKey = '';
        foreach ($cfgFirstAttrs as $__ca) {
            $__id = $__ca->getAttributeId();
            $__code = $__ca->getAttributeCode();
            $cfgFirstAttrKey .= $__id.'-'.$qcProd->getData($__code).'-';
        }
        $cfgFirstAttrKey = rtrim($cfgFirstAttrKey, '-');
        $mediaImgKey = sprintf('media_gallery/cfg_images/'.$cfgFirstAttrKey);
        $mediaImgValKey = sprintf('media_gallery/cfg_values/'.$cfgFirstAttrKey);
        $mediaGallery = [
            'images' => $prod->getData($mediaImgKey),
            'values' => $prod->getData($mediaImgValKey),
        ];
        if (empty($mediaGallery['images'])) {
            return $this;
        }
        $origMediaGallery = $qcProd->getOrigData('media_gallery');
        if (is_array($origMediaGallery)
            && !empty($origMediaGallery['images'])
        ) {
            $origImages = $origMediaGallery['images'];
            if(!is_array($origImages) && strlen($origImages) > 0) {
                $origImages = $this->_hlp->unserializeArr($origImages);
            }
            if (!is_array($origImages)) {
                $origImages = [];
            }
            $postImages = $mediaGallery['images'];
            if(!is_array($postImages) && strlen($postImages) > 0) {
                $postImages = $this->_hlp->unserializeArr($postImages);
            }
            if (!is_array($postImages)) {
                $postImages = [];
            }
            foreach ($postImages as &$postImg) {
                if (!empty($postImg['value_id'])) {
                    foreach ($origImages as $origImg) {
                        if ($origImg['file']==$postImg['file']) {
                            $postImg['value_id'] = $origImg['value_id'];
                            break;
                        }
                    }
                }
            }
            unset($postImg);
            $mediaGallery['images'] = $this->_hlp->jsonEncode($postImages);
        }
        $qcProd->setData('media_gallery', $mediaGallery);
        foreach ($qcProd->getMediaAttributes() as $_mAttr) {
            $mediaAttrKey = sprintf('media_gallery/cfg_attributes/%s/%s',
                $cfgFirstAttrKey,
                $_mAttr->getAttributeCode()
            );
            $qcProd->setData($_mAttr->getAttributeCode(), $prod->getData($mediaAttrKey));
        }
        return $this;
    }

    public function addReindexPids($product, $pIds)
    {
        $_pIds = $product->getUdprodReindexPids();
        if (!is_array($_pIds)) {
            $_pIds = [];
        }
        $product->setUdprodReindexPids(array_merge($_pIds, $pIds));
        return $this;
    }

    public function addUnpublishPids($product, $pIds)
    {
        $_pIds = $product->getUdprodUnpublishPids();
        if (!is_array($_pIds)) {
            $_pIds = [];
        }
        $product->setUdprodUnpublishPids(array_merge($_pIds, $pIds));
        return $this;
    }

    public function reindexProduct($product)
    {
        $unpublishAttrs = [
            'status'=>ProductStatus::STATUS_PENDING,
            'udprod_fix_notify' => 0,
            'udprod_approved_notify' => 0,
            'udprod_fix_notified' => 1,
            'udprod_pending_notified' => 0,
            'udprod_approved_notified' => 1,
            'udprod_fix_admin_notified' => 1,
            'udprod_pending_admin_notified' => 0,
            'udprod_approved_admin_notified' => 1,
        ];
        if ($product->getUdprodIsNew()) {
            $unpublishAttrs['udprod_pending_notify'] = 2;
            $unpublishAttrs['udprod_attributes_changed'] = '';
        } elseif (!$product->getData('udprod_pending_notify')) {
            $unpublishAttrs['udprod_pending_notify'] = 1;
        }
        if ($product->getUdprodNeedToUnpublish()) {
            $this->_productActionFactory->create()->getResource()->updateAttributes(
                [$product->getId()],
                $unpublishAttrs,
                0
            );
        }
        if (($unpubPids = $product->getUdprodUnpublishPids())) {
            $this->_productActionFactory->create()->getResource()->updateAttributes(
                $unpubPids,
                $unpublishAttrs,
                0
            );
        }
        $pIds = $product->getUdprodReindexPids();
        if (!is_array($pIds)) {
            $pIds = [];
        }
        if (!in_array($product->getId(), $pIds)) {
            $pIds[] = $product->getId();
        }
        $this->_helperCatalog->reindexPids($pIds);
    }

    public function processUdmultiPost($product, $vendor)
    {
        if ($this->_hlp->isUdmultiActive()) {
            $udmulti = $product->getData('udmulti');
            $this->_hlp->rHlp()->insertIgnore(
                'udropship_vendor_product',
                ['vendor_id'=>$vendor->getId(), 'product_id'=>$product->getId(),'status'=>$this->_hlp->getObj('Unirgy\DropshipMulti\Helper\Data')->getDefaultMvStatus()]
            );
            $this->_hlp->getObj('Unirgy\DropshipMulti\Helper\Data')->setReindexFlag(false);
            if (is_array($udmulti) && !empty($udmulti) ) {
                $this->_hlp->processDateLocaleToInternal(
                    $udmulti,
                    ['special_from_date','special_to_date'],
                    $this->_hlp->getDefaultDateFormat()
                );
                $this->_hlp->getObj('Unirgy\DropshipMulti\Helper\Data')->saveThisVendorProductsPidKeys([$product->getId()=>$udmulti], $vendor);
            }
            $this->_hlp->getObj('Unirgy\DropshipMulti\Helper\Data')->setReindexFlag(true);
        }
    }

    public function checkProduct($productId=null, $vendor=null)
    {
        if (null === $productId) {
            $productId = $this->_request->getParam('id');
        }
        if (null === $vendor) {
            $vendor = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendor();
        }
        if (!is_array($productId)) {
            $productId = [$productId];
        }
        $oldStoreId = $this->_storeManager->getStore()->getId();
        $this->_storeManager->setCurrentStore(0);
        $collection = $this->_hlp->createObj('\Unirgy\Dropship\Model\ResourceModel\ProductCollection')
            ->setFlag('udskip_price_index',1)
            ->setFlag('has_stock_status_filter', 1)
            ->addIdFilter($productId);
        if (0&&$this->_hlp->isUdmultiActive()) {
            $collection->addAttributeToFilter('entity_id', ['in'=>$vendor->getAssociatedProductIds()]);
        } else {
            $collection->addAttributeToFilter('udropship_vendor', $vendor->getId());
        }
        $collection->load();
        $this->_storeManager->setCurrentStore($oldStoreId);
        if (!$collection->getFirstItem()->getId()) {
            throw new \Exception('Product Not Found');
        }
        return $this;
    }

    protected function _processTplCfgAttrs(&$templateSku)
    {
        foreach ($templateSku as &$tplSku) {
            if (isset($tplSku['cfg_attributes'])) {
                if (!is_array($tplSku['cfg_attributes'])) {
                    $tplSku['cfg_attributes'] = [$tplSku['cfg_attributes']];
                }
                $tplSku['cfg_attributes'] = array_filter($tplSku['cfg_attributes']);
            }
        }
        unset($tplSku);
        return $this;
    }

    public function processTemplateSkus($vendor, $serialize=false)
    {
        $templateSku = $vendor->getData('udprod_template_sku');
        if ($serialize) {
            if (is_array($templateSku)) {
                $this->_processTplCfgAttrs($templateSku);
                $templateSku = serialize($templateSku);
            }
        } else {
            if (is_string($templateSku)) {
                $templateSku = unserialize($templateSku);
            }
            if (!is_array($templateSku)) {
                $templateSku = [];
            }
            $this->_processTplCfgAttrs($templateSku);
        }
        $vendor->setData('udprod_template_sku', $templateSku);
    }

    public function getEditSimpleProductData($prod, $all=false, $v=null)
    {
        if (!($v && ($v=$this->_hlp->getVendor($v)) && $v->getId())) {
        $v = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendor();
        }
        $result = [];
        $vendorData = [];
        $isUdmulti = $this->_hlp->isUdmultiActive();
        $isUdmultiPrice = $this->_hlp->isUdmultiPriceAvailable();
        $simpleProducts = $prod->getTypeInstance(true)->getUsedProducts($prod);
        $simpleProductIds = $prod->getTypeInstance(true)->getUsedProductIds($prod);
        if ($isUdmulti) {
            $vCollection = $this->_hlp->getObj('Unirgy\DropshipMulti\Helper\Data')->getMultiVendorData($simpleProductIds);
            foreach ($vCollection as $vp) {
                $vendorData[$vp->getProductId()][$vp->getVendorId()] = $vp->getData();
            }
        }
        $hasVsAttr = false;
        $vsAttrCode = $this->scopeConfig->getValue('udropship/vendor/vendor_sku_attribute', ScopeInterface::SCOPE_STORE);
        if ($vsAttrCode && $vsAttrCode!='sku'
            && ($hasVsAttr = $this->_hlp->checkProductAttribute($vsAttrCode))
        ) {
            $vsAttr = $this->_eavConfig->getAttribute('catalog_product', $vsAttrCode);
        }
        $qcAttrs = $this->getQuickCreateAttributes();
        $qcAttrs = array_keys($qcAttrs);
        $extSimpleProducts = $this->_modelProductFactory->create()->getCollection()
            ->addAttributeToSelect($qcAttrs)
            ->addAttributeToFilter([[
                'attribute' => 'entity_id',
                'in' => $simpleProductIds
            ]]);
        if ($isUdmulti) {
            $this->_hlp->getObj('Unirgy\DropshipMulti\Helper\Data')->attachMultivendorData($simpleProducts, false, true);
        }
        foreach ($simpleProducts as $simpleProd) {
            if ($extSimple = $extSimpleProducts->getItemById($simpleProd->getId())) {
                foreach ($qcAttrs as $qcAttr) {
                    $simpleProd->setData($qcAttr, $extSimple->getData($qcAttr));
                }
            }
            if (!$all && $isUdmulti && !$simpleProd->getAllMultiVendorData($v->getId())) continue;
            if (!$all && $v->getId()!=$simpleProd->getUdropshipVendor()) continue;
            $_result = [
                'simple_id' => $simpleProd->getId(),
                'name' => $simpleProd->getName(),
                'sku' => $simpleProd->getSku(),
                'status' => $simpleProd->getStatus(),
                'weight' => $simpleProd->getWeight(),
                'is_existing' => 1,
                'price' => $simpleProd->getPrice(),
                'special_price' => $simpleProd->getSpecialPrice(),
                'special_from_date' => $simpleProd->getSpecialFromDate(),
                'special_to_date' => $simpleProd->getSpecialToDate(),
                'product'=>$simpleProd
            ];
            if ($isUdmulti) {
                $udmulti = $simpleProd->getAllMultiVendorData();
                $myUdmulti = @$udmulti[$v->getId()];
                $this->_hlp->processDateInternalToLocale(
                    $myUdmulti,
                    ['special_from_date','special_to_date'],
                    $this->_hlp->getDefaultDateFormat()
                );
                $_result['udmulti'] = $myUdmulti;
                /*
                $_result['vendor_sku'] = @$myUdmulti['vendor_sku'];
                $_result['qty'] = @$myUdmulti['stock_qty'];
                $_result['udmulti_status'] = @$myUdmulti['status'];
                $_result['udmulti_state'] = @$myUdmulti['state'];
                if ($isUdmultiPrice) {
                    if (!empty($myUdmulti['vendor_price'])) {
                        $_result['price'] = $myUdmulti['vendor_price'];
                    }
                    $_result['special_price']     = @$myUdmulti['special_price'];
                    $_result['special_from_date'] = @$myUdmulti['special_from_date'];
                    $_result['special_to_date']   = @$myUdmulti['special_to_date'];
                }
                */
            } else {
                if ($hasVsAttr
                    && isset($extSimpleProducts)
                    && ($extSimple = $extSimpleProducts->getItemById($simpleProd->getId()))
                    && $extSimple->getId()
                ) {
                    $_result[$vsAttrCode] = $extSimple->getData($vsAttrCode);
                }
                $_result['stock_data'] = $this->_hlp->getStockItem($simpleProd)->getData();
            }
            $superAttrKey = [];
            foreach ($prod->getTypeInstance(true)->getUsedProductAttributes($prod) as $cfgAttr) {
                $_result[$cfgAttr->getAttributeCode()] = $simpleProd->getData($cfgAttr->getAttributeCode());
                $superAttrKey[] = $cfgAttr->getId().'='.$simpleProd->getData($cfgAttr->getAttributeCode());
            }
            $_result['super_attr_key'] = implode('-', $superAttrKey);
            $result[$simpleProd->getId()] = $_result;
        }
        return $result;
    }

    public function getFilteredSimpleProductData($product, $filters=[], $filterFlag=true)
    {
        $simpleProds = [];
        $_simpleProds = $this->getEditSimpleProductData($product);
        foreach ($_simpleProds as $simpleProd) {
            $allowUse = true;
            foreach ($filters as $fKey=>$fVal) {
                if ($filterFlag != ($fVal == $simpleProd['product']->getData($fKey))) {
                    $allowUse = false;
                    break;
                }
            }
            if ($allowUse) $simpleProds[] = $simpleProd;
        }
        return $simpleProds;
    }

    public function getCfgAttributeValues($product, $attribute, $used=null, $filters=[], $filterFlag=true)
    {
        $cfgAttribute = $product->getResource()->getAttribute($attribute);
        $values = $cfgAttribute->getSource()->getAllOptions();
        if ($used!==null) {
            $usedValues = [];
            $simpleProds = $this->getEditSimpleProductData($product);
            foreach ($simpleProds as $simpleProd) {
                $simpleProd = $simpleProd['product'];
                $usedValue = $simpleProd->getData($cfgAttribute->getAttributeCode());
                $allowUse = true;
                foreach ($filters as $fKey=>$fVal) {
                    if ($filterFlag != ($fVal == $simpleProd->getData($fKey))) {
                        $allowUse = false;
                        break;
                    }
                }
                if ($allowUse) $usedValues[] = $usedValue;
            }
            $usedValues = array_unique($usedValues);
            $_values = [];
            if ($used) {
                foreach ($usedValues as $usedValue) {
                    foreach ($values as $value) {
                        if ($used === ($value['value'] == $usedValue)) {
                            $_values[] = $value;
                        }
                    }
                }
            } else {
                foreach ($values as $value) {
                    if ($used === in_array($value['value'], $usedValues)) {
                        $_values[] = $value;
                    }
                }
            }
            $values = $_values;
        }
        return $values;
    }

    public function isAllowedGrouped($vendor=null, $setId=null)
    {
        if (null === $setId) {
            $setId = $this->_request->getParam('set_id');
        }
        $_setId = $setId;
        if ($setId instanceof Product) {
            if (!($_setId = $setId->getUdprodAttributeSetKey())) {
                $_setId = $setId->getAttributeSetId();
            }
        }
        list($__setId) = explode('-', $_setId);
        $setId = $_setId;
        $_setId = $__setId;
        $allowedGrouped = false;
        if ($vendor==null) {
            $vendor = $this->getVendor();
        }
        if ($vendor) {
            $vTplSku = $this->getUdprodTemplateSku($vendor);
            if (isset($vTplSku[$setId]) && !empty($vTplSku[$setId]['allow_grouped'])) {
                $allowedGrouped = $vTplSku[$setId]['allow_grouped'];
            }
            if (empty($allowedGrouped) && isset($vTplSku[$_setId]) && !empty($vTplSku[$_setId]['cfg_attributes'])) {
                $allowedGrouped = $vTplSku[$_setId]['allow_grouped'];
            }
        }
        $gTplSku = $this->getGlobalTemplateSkuConfig();

        if (empty($allowedGrouped) && isset($gTplSku[$setId]) &&  !empty($gTplSku[$setId]['allow_grouped'])) {
            $allowedGrouped = $gTplSku[$setId]['allow_grouped'];
        }
        if (empty($allowedGrouped) && isset($gTplSku[$_setId]) &&  !empty($gTplSku[$_setId]['allow_grouped'])) {
            $allowedGrouped = $gTplSku[$_setId]['allow_grouped'];
        }
        return $allowedGrouped;
    }

    public function isAllowedDownloadable($vendor=null, $setId=null)
    {
        if (null === $setId) {
            $setId = $this->_request->getParam('set_id');
        }
        $_setId = $setId;
        if ($setId instanceof Product) {
            if (!($_setId = $setId->getUdprodAttributeSetKey())) {
                $_setId = $setId->getAttributeSetId();
            }
        }
        list($__setId) = explode('-', $_setId);
        $setId = $_setId;
        $_setId = $__setId;
        $allowedDownloadable = false;
        if ($vendor==null) {
            $vendor = $this->getVendor();
        }
        if ($vendor) {
            $vTplSku = $this->getUdprodTemplateSku($vendor);
            if (isset($vTplSku[$setId]) && !empty($vTplSku[$setId]['allow_downloadable'])) {
                $allowedDownloadable = $vTplSku[$setId]['allow_downloadable'];
            }
            if (empty($allowedDownloadable) && isset($vTplSku[$_setId]) && !empty($vTplSku[$_setId]['cfg_attributes'])) {
                $allowedDownloadable = $vTplSku[$_setId]['allow_downloadable'];
            }
        }
        $gTplSku = $this->getGlobalTemplateSkuConfig();

        if (empty($allowedDownloadable) && isset($gTplSku[$setId]) &&  !empty($gTplSku[$setId]['allow_downloadable'])) {
            $allowedDownloadable = $gTplSku[$setId]['allow_downloadable'];
        }
        if (empty($allowedDownloadable) && isset($gTplSku[$_setId]) &&  !empty($gTplSku[$_setId]['allow_downloadable'])) {
            $allowedDownloadable = $gTplSku[$_setId]['allow_downloadable'];
        }
        return $allowedDownloadable;
    }

    public function isAllowedVirtual($vendor=null, $setId=null)
    {
        if (null === $setId) {
            $setId = $this->_request->getParam('set_id');
        }
        $_setId = $setId;
        if ($setId instanceof Product) {
            if (!($_setId = $setId->getUdprodAttributeSetKey())) {
                $_setId = $setId->getAttributeSetId();
            }
        }
        list($__setId) = explode('-', $_setId);
        $setId = $_setId;
        $_setId = $__setId;
        $allowedVirtual = false;
        if ($vendor==null) {
            $vendor = $this->getVendor();
        }
        if ($vendor) {
            $vTplSku = $this->getUdprodTemplateSku($vendor);
            if (isset($vTplSku[$setId]) && !empty($vTplSku[$setId]['allow_virtual'])) {
                $allowedVirtual = $vTplSku[$setId]['allow_virtual'];
            }
            if (empty($allowedVirtual) && isset($vTplSku[$_setId]) && !empty($vTplSku[$_setId]['cfg_attributes'])) {
                $allowedVirtual = $vTplSku[$_setId]['allow_virtual'];
            }
        }
        $gTplSku = $this->getGlobalTemplateSkuConfig();

        if (empty($allowedVirtual) && isset($gTplSku[$setId]) &&  !empty($gTplSku[$setId]['allow_virtual'])) {
            $allowedVirtual = $gTplSku[$setId]['allow_virtual'];
        }
        if (empty($allowedVirtual) && isset($gTplSku[$_setId]) &&  !empty($gTplSku[$_setId]['allow_virtual'])) {
            $allowedVirtual = $gTplSku[$_setId]['allow_virtual'];
        }
        return $allowedVirtual;
    }
    public function isAllowedSimple($vendor=null, $setId=null)
    {
        if (null === $setId) {
            $setId = $this->_request->getParam('set_id');
        }
        $_setId = $setId;
        if ($setId instanceof Product) {
            if (!($_setId = $setId->getUdprodAttributeSetKey())) {
                $_setId = $setId->getAttributeSetId();
            }
        }
        list($__setId) = explode('-', $_setId);
        $setId = $_setId;
        $_setId = $__setId;
        $allowedSimple = true;
        if ($vendor==null) {
            $vendor = $this->getVendor();
        }
        if ($vendor) {
            $vTplSku = $this->getUdprodTemplateSku($vendor);
            if (isset($vTplSku[$setId]) && !empty($vTplSku[$setId]['disallow_simple'])) {
                $allowedSimple = !$vTplSku[$setId]['disallow_simple'];
            }
            if (!empty($allowedSimple) && isset($vTplSku[$_setId]) && !empty($vTplSku[$_setId]['cfg_attributes'])) {
                $allowedSimple = !$vTplSku[$_setId]['disallow_simple'];
            }
        }
        $gTplSku = $this->getGlobalTemplateSkuConfig();

        if (!empty($allowedSimple) && isset($gTplSku[$setId]) &&  !empty($gTplSku[$setId]['disallow_simple'])) {
            $allowedSimple = !$gTplSku[$setId]['disallow_simple'];
        }
        if (!empty($allowedSimple) && isset($gTplSku[$_setId]) &&  !empty($gTplSku[$_setId]['disallow_simple'])) {
            $allowedSimple = !$gTplSku[$_setId]['disallow_simple'];
        }
        return $allowedSimple;
    }

    public function hasTplConfigurableAttributes($vendor=null, $setId=null)
    {
        return (bool)$this->getTplConfigurableAttributes($vendor, $setId);
    }
    public function getTplConfigurableAttributes($vendor=null, $setId=null)
    {
        if (null === $setId) {
            $setId = $this->_request->getParam('set_id');
        }
        $_setId = $setId;
        if ($setId instanceof Product) {
            if (!($_setId = $setId->getUdprodAttributeSetKey())) {
                $_setId = $setId->getAttributeSetId();
            }
        }
        list($__setId) = explode('-', $_setId);
        $setId = $_setId;
        $_setId = $__setId;
        $tplCfgAttrs = [];
        if ($vendor==null) {
            $vendor = $this->getVendor();
        }
        if ($vendor) {
            $vTplSku = $this->getUdprodTemplateSku($vendor);
            if (isset($vTplSku[$setId]) && !empty($vTplSku[$setId]['cfg_attributes'])) {
                $tplCfgAttrs = $vTplSku[$setId]['cfg_attributes'];
            }
            if (empty($tplCfgAttrs) && isset($vTplSku[$_setId]) && !empty($vTplSku[$_setId]['cfg_attributes'])) {
                $tplCfgAttrs = $vTplSku[$_setId]['cfg_attributes'];
            }
        }
        $gTplSku = $this->getGlobalTemplateSkuConfig();

        if (empty($tplCfgAttrs) && isset($gTplSku[$setId]) &&  !empty($gTplSku[$setId]['cfg_attributes'])) {
            $tplCfgAttrs = $gTplSku[$setId]['cfg_attributes'];
        }
        if (empty($tplCfgAttrs) && isset($gTplSku[$_setId]) &&  !empty($gTplSku[$_setId]['cfg_attributes'])) {
            $tplCfgAttrs = $gTplSku[$_setId]['cfg_attributes'];
        }
        return $tplCfgAttrs;
    }
    public function getConfigurableAttributes($prod, $isNew)
    {
        $vendor = $this->_hlp->getVendor($prod->getUdropshipVendor());
        $usedCfgAttrs = [];
        if ($prod->getId() && !$isNew) {
            $usedCfgAttrs = $prod->getTypeInstance(true)->getUsedProductAttributes($prod);
        } else {
            $cfgAttributes = $prod->getTypeInstance(true)->getSetAttributes($prod);
            $usedCfgAttrIds = $this->getTplConfigurableAttributes(
                $vendor,
                $prod
            );
            if (is_array($usedCfgAttrIds)) {
                foreach ($cfgAttributes as $cfgAttribute) {
                    if (false !== ($sortKey = array_search($cfgAttribute->getId(), $usedCfgAttrIds))) {
                        $usedCfgAttrs[$sortKey] = $cfgAttribute;
                    }
                }
            }
            ksort($usedCfgAttrs, SORT_NUMERIC);
        }
        return $usedCfgAttrs;
    }
    public function getCfgFirstAttributes($product, $isNew=null)
    {
        $isNew = null === $isNew ? !$product->getId() : $isNew;
        $attrs = $this->getIdentifyImageAttributes($product, $isNew);
        if (empty($attrs)) {
            $attrs[] = $this->getCfgFirstAttribute($product, $isNew);
        }
        return $attrs;
    }
    public function getCfgFirstAttribute($product, $isNew=null)
    {
        $isNew = null === $isNew ? !$product->getId() : $isNew;
        $cfgAttributes = $this->getConfigurableAttributes($product, $isNew);
        $cfgAttribute = !empty($cfgAttributes) ? array_shift($cfgAttributes) : false;
        return $cfgAttribute;
    }
    public function getCfgFirstAttributesValueTuples($product, $pair=false)
    {
        $cfgAttributes = $this->getCfgFirstAttributes($product);
        $usedValueTuples = [];
        $simpleProds = $this->getEditSimpleProductData($product);
        foreach ($simpleProds as $simpleProd) {
            $simpleProd = $simpleProd['product'];
            $usedValue = [];
            foreach ($cfgAttributes as $__i=>$cfgAttribute) {
                $usedValue[] = $simpleProd->getData($cfgAttribute->getAttributeCode());
            }
            $usedValueTuples[implode('-',$usedValue)] = $usedValue;
        }
        $usedValueTuples = array_values($usedValueTuples);
        $valueTuples = [];
        foreach ($usedValueTuples as $usedValue) {
            $valueTuple = [];
            foreach ($cfgAttributes as $__i=>$cfgAttribute) {
                $values = $cfgAttribute->getSource()->getAllOptions();
                foreach ($values as $value) {
                    if ($value['value']==$usedValue[$__i]) {
                        $valueTuple[$__i] = $pair ? $value : $value['value'];
                    }
                }
            }
            $valueTuples[] = $valueTuple;
        }
        return $valueTuples;
    }
    public function getCfgFirstAttributeValues($product, $used=null, $filters=[], $filterFlag=true)
    {
        return $this->getCfgAttributeValues($product, $this->getCfgFirstAttribute($product), $used, $filters, $filterFlag);
    }
    public function getTplIdentifyImageAttributes($vendor, $setId=null)
    {
        if (null === $setId) {
            $setId = $this->_request->getParam('set_id');
        }
        $tplCfgAttrs = [];
        $vTplSku = $this->getUdprodTemplateSku($vendor);
        if (isset($vTplSku[$setId]) && !empty($vTplSku[$setId]['cfg_identify_image'])) {
            $tplCfgAttrs = $vTplSku[$setId]['cfg_identify_image'];
        }
        $gTplSku = $this->getGlobalTemplateSkuConfig();
        if (empty($tplCfgAttrs) && isset($gTplSku[$setId]) &&  !empty($gTplSku[$setId]['cfg_identify_image'])) {
            $tplCfgAttrs = $gTplSku[$setId]['cfg_identify_image'];
        }
        return $tplCfgAttrs;
    }
    public function getIdentifyImageAttributes($prod, $isNew)
    {
        $vendor = $this->_hlp->getVendor($prod->getUdropshipVendor());
        $usedCfgAttrs = [];
        if ($prod->getId() && !$isNew) {
            $_usedCfgAttrs = $prod->getTypeInstance(true)->getConfigurableAttributes($prod);
            foreach ($_usedCfgAttrs as $_usedCfgAttr) {
                if ($_usedCfgAttr->getIdentifyImage()) {
                    $usedCfgAttrs[] = $_usedCfgAttr->getProductAttribute();
                }
            }
        } else {
            $cfgAttributes = $prod->getTypeInstance(true)->getSetAttributes($prod);
            $usedCfgAttrIds = $this->getTplIdentifyImageAttributes($vendor);
            if (is_array($usedCfgAttrIds)) {
                foreach ($cfgAttributes as $cfgAttribute) {
                    if (in_array($cfgAttribute->getId(), $usedCfgAttrIds)) {
                        $usedCfgAttrs[] = $cfgAttribute;
                    }
                }
            }
        }
        return $usedCfgAttrs;
    }
    public function isMyProduct($product)
    {
        return !$product->getId()
            || $this->getVendor()->getId() == $this->getProductVendor($product)->getId();
    }
    public function getVendor()
    {
        return ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendor();
    }
    public function getProductVendor($product)
    {
        return $this->_hlp->getVendor($product->getUdropshipVendor());
    }
    public function processCfgPriceChanges($prod, $pIds)
    {
        $priceChanges = [];
        foreach (['price', 'special_price', 'special_from_date', 'special_to_date'] as $pKey) {
            if ($prod->dataHasChangedFor($pKey)) {
                $priceChanges[$pKey] = $prod->getData($pKey);
            }
        }
        if (!empty($priceChanges)) {
            $this->_productActionFactory->create()->getResource()->updateAttributes($pIds, $priceChanges, 0);
        }
        return $this;
    }

    public function getProdSetIdLabel($prod)
    {
        $options = $this->_setCollection
            ->setEntityTypeFilter($this->_modelProductFactory->create()->getResource()->getEntityType()->getId())
            ->load()
            ->toOptionHash();
        return @$options[$prod->getAttributeSetId()];
    }

    public function getUseTplProdWebsiteBySetId($setId)
    {
        $_setId = $setId;
        if ($setId instanceof Product) {
            if (!($_setId = $setId->getUdprodAttributeSetKey())) {
                $_setId = $setId->getAttributeSetId();
            }
        }
        list($__setId) = explode('-', $_setId);
        $gTplSku = $this->getGlobalTemplateSkuConfig();
        $result = @$gTplSku[$_setId]['use_product_website'];
        if (!isset($gTplSku[$_setId])) {
            $result = @$gTplSku[$__setId]['use_product_website'];
        }
        return $result;
    }
    public function getUseTplProdCategoryBySetId($setId)
    {
        $_setId = $setId;
        if ($setId instanceof Product) {
            if (!($_setId = $setId->getUdprodAttributeSetKey())) {
                $_setId = $setId->getAttributeSetId();
            }
        }
        list($__setId) = explode('-', $_setId);
        $gTplSku = $this->getGlobalTemplateSkuConfig();
        $result = @$gTplSku[$_setId]['use_product_category'];
        if (!isset($gTplSku[$_setId])) {
            $result = @$gTplSku[$__setId]['use_product_category'];
        }
        return $result;
    }

    public function getDefaultWebsiteBySetId($setId)
    {
        $_setId = $setId;
        if ($setId instanceof Product) {
            if (!($_setId = $setId->getUdprodAttributeSetKey())) {
                $_setId = $setId->getAttributeSetId();
            }
        }
        list($__setId) = explode('-', $_setId);
        $gTplSku = $this->getGlobalTemplateSkuConfig();
        $result = @$gTplSku[$_setId]['website'];
        if (!isset($gTplSku[$_setId])) {
            $result = @$gTplSku[$__setId]['website'];
        }
        return $result;
    }
    public function getDefaultCategoryBySetId($setId)
    {
        $_setId = $setId;
        if ($setId instanceof Product) {
            if (!($_setId = $setId->getUdprodAttributeSetKey())) {
                $_setId = $setId->getAttributeSetId();
            }
        }
        list($__setId) = explode('-', $_setId);
        $gTplSku = $this->getGlobalTemplateSkuConfig();
        $result = @$gTplSku[$_setId]['category'];
        if (!isset($gTplSku[$_setId])) {
            $result = @$gTplSku[$__setId]['category'];
        }
        return $result;
    }
    public function getDefaultColorByImage($p)
    {
        $images = $p->getMediaGallery('images');
        $cfgFirstAttr = $this->getCfgFirstAttribute($p);
        $mainColorValue = null;
        if (is_array($images) && $cfgFirstAttr) {
            $cfgFirstAttrId = $cfgFirstAttr->getId();
            foreach ($images as $image) {
                if (isset($image['super_attribute'][$cfgFirstAttrId])
                    && $image['file'] == $p->getThumbnail()
                ) {
                    $mainColorValue = $image['super_attribute'][$cfgFirstAttrId];
                    break;
                }
            }
        }
        return $mainColorValue;
    }

    public function getHideEditFields()
    {
        $hideFields = explode(',', $this->scopeConfig->getValue('udropship/microsite/hide_product_attributes', ScopeInterface::SCOPE_STORE));
        $hideFields[] = 'udropship_vendor';
        //$hideFields[] = 'tier_price';
        $hideFields[] = 'gallery';
        $hideFields[] = 'media_gallery';
        $hideFields[] = 'small_image';
        $hideFields[] = 'thumbnail';
        $hideFields[] = 'image';
        $hideFields[] = 'recurring_profile';
        $hideFields[] = 'category_ids';
        $hideFields[] = '';
        return $hideFields;
    }
    public function getQCNumericAttributes()
    {
        return ['weight','status','price','special_price'];
    }
    public function getQCForcedNumericAttributes()
    {
        return ['status'];
    }
    public function getQCSelectAttributes()
    {
        return ['status'=>0];
    }
    public function getMvNumericAttributes()
    {
        return ['vendor_cost','stock_qty','priority','shipping_price','backorders','vendor_price','status','special_price'];
    }
    public function getMvForcedNumericAttributes()
    {
        return ['status'];
    }
    public function getMvSelectAttributes()
    {
        return ['status'=>0,'state'=>0];
    }
    public function getSdNumericAttributes()
    {
        return ['is_in_stock','qty'];
    }
    public function getSdForcedNumericAttributes()
    {
        return ['is_in_stock'];
    }
    public function getSdSelectAttributes()
    {
        return ['is_in_stock'=>0];
    }
    public function getQuickCreateAttributes()
    {
        $entityType = $this->_eavConfig->getEntityType('catalog_product');
        $hideFields = $this->getHideEditFields();
        $attrs = $entityType->getAttributeCollection()
            ->addFieldToFilter('is_visible', 1)
            ->setOrder('frontend_label', 'asc');
        $qcAttrs = [];
        foreach ($attrs as $a) {
            if (in_array($a->getAttributeCode(), $this->getQuickCreateAllowedAttributes())
                && !in_array($a->getAttributeCode(), $hideFields)
            ) {
                $qcAttrs[$a->getAttributeCode()] = $a;
            }
        }
        return $qcAttrs;
    }
    public function getQuickCreateAllowedAttributes()
    {
        $qcAttrCodes = ['weight','sku','name','status','price','special_price','special_from_date','special_to_date'];
        $vsAttrCode = $this->scopeConfig->getValue('udropship/vendor/vendor_sku_attribute', ScopeInterface::SCOPE_STORE);
        if ($vsAttrCode && $vsAttrCode!='sku'
            && ($hasVsAttr = $this->_hlp->checkProductAttribute($vsAttrCode))
        ) {
            $qcAttrCodes[] = $vsAttrCode;
        }
        return $qcAttrCodes;
    }
    public function getQuickCreateFieldsConfig()
    {
        $entityType = $this->_eavConfig->getEntityType('catalog_product');
        $hideFields = $this->getHideEditFields();
        $attrs = $entityType->getAttributeCollection()
            ->addFieldToFilter('is_visible', 1)
            ->setOrder('frontend_label', 'asc');
        $editFields = [];
        $paValues = [];
        foreach ($attrs as $a) {
            if (in_array($a->getAttributeCode(), $this->getQuickCreateAllowedAttributes())
                && !in_array($a->getAttributeCode(), $hideFields)
            ) {
                $paValues['product.'.$a->getAttributeCode()] = $a->getFrontendLabel().' ['.$a->getAttributeCode().']';
            }
        }
        $editFields['product']['label'] = 'Product Attributes';
        $editFields['product']['values'] = $paValues;
        if ($this->_hlp->isUdmultiActive()) {
            $editFields['udmulti']['label'] = __('Vendor Specific Fields');
            $editFields['udmulti']['values']  = $this->getVendorEditFieldsConfig();
        } else {
            $sdValues['stock_data.qty'] = __('Stock Qty').' [stock_item.qty]';
            $sdValues['stock_data.is_in_stock'] = __('Stock Status').' [stock_item.is_in_stock]';
            $editFields['stock_data']['label'] = __('Stock Item Fields');
            $editFields['stock_data']['values']  = $sdValues;
        }
        return $editFields;
    }
    public function getEditFieldsConfig()
    {
        $entityType = $this->_eavConfig->getEntityType('catalog_product');
        $hideFields = $this->getHideEditFields();
        $attrs = $entityType->getAttributeCollection()
            ->addFieldToFilter('is_visible', 1)
            ->setOrder('frontend_label', 'asc');
        $editFields = [];
        $paValues = [];
        foreach ($attrs as $a) {
            if (!in_array($a->getAttributeCode(), $hideFields)) {
                $paValues['product.'.$a->getAttributeCode()] = $a->getFrontendLabel().' ['.$a->getAttributeCode().']';
            }
        }
        $editFields['product']['label'] = 'Product Attributes';
        $editFields['product']['values'] = $paValues;

        $editFields['system']['label'] = 'System Attributes';
        $editFields['system']['values'] = [
            'system.product_categories' => __('Categories'),
            'system.product_websites'   => __('Websites')
        ];

        if ($this->_hlp->isUdmultiActive()) {
            $editFields['udmulti']['label'] = __('Vendor Specific Fields');
            $editFields['udmulti']['values']  = $this->getVendorEditFieldsConfig();
        } else {
            $sdValues['stock_data.qty'] = __('Stock Qty').' [stock_item.qty]';
            $sdValues['stock_data.is_in_stock'] = __('Stock Status').' [stock_item.is_in_stock]';
            $sdValues['stock_data.manage_stock'] = __('Manage Stock').' [stock_item.manage_stock]';
            $sdValues['stock_data.backorders'] = __('Backorders').' [stock_item.backorders]';
            $sdValues['stock_data.min_qty'] = __('Qty for Item\'s Status to Become Out of Stock').' [stock_item.min_qty]';
            $sdValues['stock_data.min_sale_qty'] = __('Minimum Qty Allowed in Shopping Cart').' [stock_item.min_sale_qty]';
            $sdValues['stock_data.max_sale_qty'] = __('Maximum Qty Allowed in Shopping Cart').' [stock_item.max_sale_qty]';
            $editFields['stock_data']['label'] = __('Stock Item Fields');
            $editFields['stock_data']['values']  = $sdValues;
        }
        return $editFields;
    }
    public function getEditFieldsConfigSelect2Json()
    {
        $fConfig = $this->getEditFieldsConfig();
        $fRes = [['id'=>'','text'=>__('* Please select')]];
        foreach ($fConfig as $efc) {
            if (!is_array($efc['values'])) continue;
            $_fRes = [
                'text' => $efc['label']
            ];
            foreach ($efc['values'] as $fId=>$fLbl) {
                $_fRes['children'][] = [
                    'id' => $fId,
                    'text' => $fLbl,
                ];
            }
            $fRes[] = $_fRes;
        }
        return $this->_hlp->jsonEncode($fRes);
    }
    public function getVendorEditFieldsConfig()
    {
        $udmHlp = $this->_hlp->getObj('Unirgy\DropshipMulti\Helper\Data');
        $udmv['udmulti.vendor_sku']        = __('Vendor SKU ').' [udmulti.vendor_sku]';
        $udmv['udmulti.stock_qty']         = __('Vendor Stock Qty ').' [udmulti.stock_qty]';
        $udmv['udmulti.vendor_cost']       = __('Vendor Cost ').' [udmulti.vendor_cost]';
        $udmv['udmulti.status']            = __('Vendor Status ').' [udmulti.status]';
        $udmv['udmulti.backorders']        = __('Vendor Backorders ').' [udmulti.backorders]';
        if ($this->_hlp->isUdmultiPriceAvailable()) {
        $udmv['udmulti.vendor_price']      = __('Vendor Price ').' [udmulti.vendor_price]';
        $udmv['udmulti.group_price']       = __('Vendor Group Price ').' [udmulti.group_price]';
        $udmv['udmulti.tier_price']        = __('Vendor Tier Price ').' [udmulti.tier_price]';
        $udmv['udmulti.special_price']     = __('Vendor Special Price ').' [udmulti.special_price]';
        $udmv['udmulti.special_from_date'] = __('Vendor Special From Date ').' [udmulti.special_from_date]';
        $udmv['udmulti.special_to_date']   = __('Vendor Special To Date ').' [udmulti.special_to_date]';
        $udmv['udmulti.freeshipping']      = __('Vendor Free Shipping ').' [udmulti.freeshipping]';
        $udmv['udmulti.shipping_price']    = __('Vendor Shipping Price ').' [udmulti.shipping_price]';
        $udmv['udmulti.state']             = __('Vendor State(Condition) ').' [udmulti.state]';
        $udmv['udmulti.state_descr']       = __('Vendor State Description ').' [udmulti.state_descr]';
        $udmv['udmulti.vendor_title']      = __('Vendor Title ').' [udmulti.vendor_title]';
        }
        return $udmv;
    }

    public function setNeedToUnpublish($product, $action)
    {
        $v = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendor();
        $unpublishActions = $this->scopeConfig->getValue('udprod/general/unpublish_actions', ScopeInterface::SCOPE_STORE);
        if ($v->getData('is_custom_udprod_unpublish_actions')) {
            $unpublishActions = $v->getData('udprod_unpublish_actions');
        }
        if (!is_array($unpublishActions)) {
            $unpublishActions = array_filter(explode(',', $unpublishActions));
        }
        if ((empty($unpublishActions) || in_array($action, $unpublishActions) || in_array('all', $unpublishActions))
            && !in_array('none', $unpublishActions)
            || $product->getStatus()==ProductStatus::STATUS_FIX
            && in_array($action, ['attribute_changed','image_added'])
        ) {
            $product->setUdprodNeedToUnpublish(true);
        }
    }

    public function isAllowedTypeOfProduct($typeOfProduct, $vendor=null)
    {
        if ($vendor==null) {
            $vendor = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendor();
        }
        $at = $this->scopeConfig->getValue('udprod/general/allowed_types', ScopeInterface::SCOPE_STORE);
        if ($vendor->getData('is_custom_udprod_allowed_types')) {
            $at = $vendor->getData('udprod_allowed_types');
        }
        if (!is_array($at)) {
            $at = array_filter(explode(',', $at));
        }
        return (empty($at) || in_array($typeOfProduct, $at) || in_array('*all*', $at))
            && !in_array('*none*', $at);
    }

    public function isPendingNotifyVendor()
    {
        return $this->scopeConfig->isSetFlag('udprod/notification/send_pending_notifications', ScopeInterface::SCOPE_STORE);
    }
    public function isPendingNotifyAdmin()
    {
        return $this->scopeConfig->isSetFlag('udprod/notification/send_pending_admin_notifications', ScopeInterface::SCOPE_STORE);
    }

    public function isApprovedNotifyVendor()
    {
        return $this->scopeConfig->isSetFlag('udprod/notification/send_approved_notifications', ScopeInterface::SCOPE_STORE);
    }
    public function isApprovedNotifyAdmin()
    {
        return $this->scopeConfig->isSetFlag('udprod/notification/send_approved_admin_notifications', ScopeInterface::SCOPE_STORE);
    }
    public function isFixNotifyVendor()
    {
        return $this->scopeConfig->isSetFlag('udprod/notification/send_fix_notifications', ScopeInterface::SCOPE_STORE);
    }
    public function isFixNotifyAdmin()
    {
        return $this->scopeConfig->isSetFlag('udprod/notification/send_fix_admin_notifications', ScopeInterface::SCOPE_STORE);
    }

    public function sendPendingNotificationEmail($products, $vendor)
    {
        $store = $this->_storeManager->getDefaultStoreView();
        if ($this->isPendingNotifyVendor() && !empty($products)) {

            $this->inlineTranslation->suspend();

            $data = [
                'store' => $store,
                'store_name' => $store->getName(),
                'vendor'      => $vendor,
                'vendor_name' => $vendor->getVendorName(),
                'vendor_email' => $vendor->getEmail(),
            ];
            $data['notification_grid'] = $this->_productAlertHelper->createBlock('Magento\Framework\View\Element\Template')
                ->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/notification/pending.phtml')
                ->setProducts($products)
                ->toHtml();

            $this->_transportBuilder->setTemplateIdentifier(
                $this->_hlp->getScopeConfig('udprod/notification/pending_vendor_email_template', $store)
            )->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ]
            )->setTemplateVars(
                $data
            )->setFrom(
                $this->_hlp->getScopeConfig('udropship/vendor/vendor_email_identity', $store)
            )->addTo(
                $vendor->getEmail(),
                $vendor->getVendorName()
            );

            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();

            $this->_productAction
                ->updateAttributes(array_keys($products), ['udprod_pending_notified' => 1], 0);

            $this->inlineTranslation->resume();

        }
    }
    public function sendApprovedNotificationEmail($products, $vendor)
    {
        $store = $this->_storeManager->getDefaultStoreView();
        if ($this->isApprovedNotifyVendor() && !empty($products)) {

            $this->inlineTranslation->suspend();

            $data = [
                'store' => $store,
                'store_name' => $store->getName(),
                'vendor'      => $vendor,
                'vendor_name' => $vendor->getVendorName(),
                'vendor_email' => $vendor->getEmail(),
            ];
            $data['notification_grid'] = $this->_productAlertHelper->createBlock('Magento\Framework\View\Element\Template')
                ->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/notification/approved.phtml')
                ->setProducts($products)
                ->toHtml();

            $this->_transportBuilder->setTemplateIdentifier(
                $this->_hlp->getScopeConfig('udprod/notification/approved_vendor_email_template', $store)
            )->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ]
            )->setTemplateVars(
                $data
            )->setFrom(
                $this->_hlp->getScopeConfig('udropship/vendor/vendor_email_identity', $store)
            )->addTo(
                $vendor->getEmail(),
                $vendor->getVendorName()
            );

            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();

            $this->_productAction
                ->updateAttributes(array_keys($products), ['udprod_approved_notified' => 1], 0);
        }
    }
    public function sendFixNotificationEmail($products, $vendor)
    {
        $store = $this->_storeManager->getDefaultStoreView();
        if ($this->isFixNotifyVendor() && !empty($products)) {

            $this->inlineTranslation->suspend();

            $data = [
                'store' => $store,
                'store_name' => $store->getName(),
                'vendor'      => $vendor,
                'vendor_name' => $vendor->getVendorName(),
                'vendor_email' => $vendor->getEmail(),
            ];
            $data['notification_grid'] = $this->_productAlertHelper->createBlock('Magento\Framework\View\Element\Template')
                ->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/notification/fix.phtml')
                ->setProducts($products)
                ->toHtml();

            $this->_transportBuilder->setTemplateIdentifier(
                $this->_hlp->getScopeConfig('udprod/notification/fix_vendor_email_template', $store)
            )->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ]
            )->setTemplateVars(
                $data
            )->setFrom(
                $this->_hlp->getScopeConfig('udropship/vendor/vendor_email_identity', $store)
            )->addTo(
                $vendor->getEmail(),
                $vendor->getVendorName()
            );

            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();

            $this->_productAction
                ->updateAttributes(array_keys($products), ['udprod_fix_notified' => 1], 0);
        }
    }

    public function sendPendingAdminNotificationEmail($products, $vendor)
    {
        $store = $this->_storeManager->getDefaultStoreView();
        if ($this->isPendingNotifyAdmin() && !empty($products)) {

            $this->inlineTranslation->suspend();

            $data = [
                'store' => $store,
                'store_name' => $store->getName(),
                'vendor'      => $vendor,
                'vendor_name' => $vendor->getVendorName(),
                'vendor_email' => $vendor->getEmail(),
            ];
            $data['notification_grid'] = $this->_productAlertHelper->createBlock('Magento\Framework\View\Element\Template')
                ->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/notification/pending.phtml')
                ->setProducts($products)
                ->toHtml();

            $adminIdent = $this->_hlp->getScopeConfig('udprod/notification/admin_email_identity', $store);

            $this->_transportBuilder->setTemplateIdentifier(
                $this->_hlp->getScopeConfig('udprod/notification/pending_admin_email_template', $store)
            )->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ]
            )->setTemplateVars(
                $data
            )->setFrom(
                $this->_hlp->getScopeConfig('udropship/vendor/vendor_email_identity', $store)
            )->addTo(
                $this->scopeConfig->getValue('trans_email/ident_' . $adminIdent . '/email', ScopeInterface::SCOPE_STORE, $store),
                $this->scopeConfig->getValue('trans_email/ident_' . $adminIdent . '/name', ScopeInterface::SCOPE_STORE, $store)
            );

            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();

            $this->_productAction
                ->updateAttributes(array_keys($products), ['udprod_pending_admin_notified' => 1], 0);
        }
    }
    public function sendApprovedAdminNotificationEmail($products, $vendor)
    {
        $store = $this->_storeManager->getDefaultStoreView();
        if ($this->isApprovedNotifyAdmin() && !empty($products)) {

            $this->inlineTranslation->suspend();

            $data = [
                'store' => $store,
                'store_name' => $store->getName(),
                'vendor'      => $vendor,
                'vendor_name' => $vendor->getVendorName(),
                'vendor_email' => $vendor->getEmail(),
            ];
            $data['notification_grid'] = $this->_productAlertHelper->createBlock('Magento\Framework\View\Element\Template')
                ->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/notification/approved.phtml')
                ->setProducts($products)
                ->toHtml();

            $adminIdent = $this->_hlp->getScopeConfig('udprod/notification/admin_email_identity', $store);

            $this->_transportBuilder->setTemplateIdentifier(
                $this->_hlp->getScopeConfig('udprod/notification/approved_vendor_email_template', $store)
            )->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ]
            )->setTemplateVars(
                $data
            )->setFrom(
                $this->_hlp->getScopeConfig('udropship/vendor/vendor_email_identity', $store)
            )->addTo(
                $this->scopeConfig->getValue('trans_email/ident_' . $adminIdent . '/email', ScopeInterface::SCOPE_STORE, $store),
                $this->scopeConfig->getValue('trans_email/ident_' . $adminIdent . '/name', ScopeInterface::SCOPE_STORE, $store)
            );

            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();

            $this->_productAction
                ->updateAttributes(array_keys($products), ['udprod_approved_admin_notified' => 1], 0);
        }
    }
    public function sendFixAdminNotificationEmail($products, $vendor)
    {
        $store = $this->_storeManager->getDefaultStoreView();
        if ($this->isFixNotifyAdmin() && !empty($products)) {

            $this->inlineTranslation->suspend();

            $data = [
                'store' => $store,
                'store_name' => $store->getName(),
                'vendor'      => $vendor,
                'vendor_name' => $vendor->getVendorName(),
                'vendor_email' => $vendor->getEmail(),
            ];
            $data['notification_grid'] = $this->_productAlertHelper->createBlock('Magento\Framework\View\Element\Template')
                ->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/notification/fix.phtml')
                ->setProducts($products)
                ->toHtml();

            $adminIdent = $this->_hlp->getScopeConfig('udprod/notification/admin_email_identity', $store);

            $this->_transportBuilder->setTemplateIdentifier(
                $this->_hlp->getScopeConfig('udprod/notification/fix_vendor_email_template', $store)
            )->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ]
            )->setTemplateVars(
                $data
            )->setFrom(
                $this->_hlp->getScopeConfig('udropship/vendor/vendor_email_identity', $store)
            )->addTo(
                $this->scopeConfig->getValue('trans_email/ident_' . $adminIdent . '/email', ScopeInterface::SCOPE_STORE, $store),
                $this->scopeConfig->getValue('trans_email/ident_' . $adminIdent . '/name', ScopeInterface::SCOPE_STORE, $store)
            );

            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();

            $this->_productAction
                ->updateAttributes(array_keys($products), ['udprod_fix_admin_notified' => 1], 0);
        }
    }

}
