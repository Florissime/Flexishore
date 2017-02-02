<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Form as DataForm;
use Magento\Framework\Data\Form\AbstractForm;
use Magento\Framework\Filter\Sprintf;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorProduct\Helper\Data as DropshipVendorProductHelperData;
use Unirgy\DropshipVendorProduct\Helper\Form;
use Unirgy\Dropship\Helper\Catalog;
use Unirgy\Dropship\Helper\Data as HelperData;

class Product extends Template
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var Catalog
     */
    protected $_helperCatalog;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var DropshipVendorProductHelperData
     */
    protected $_prodHlp;

    /**
     * @var Form
     */
    protected $_prodHlpForm;

    public function __construct(
        Context $context,
        Registry $frameworkRegistry, 
        Catalog $helperCatalog,
        HelperData $helperData, 
        DropshipVendorProductHelperData $dropshipVendorProductHelperData, 
        Form $helperForm,
        array $data = [])
    {
        $this->_coreRegistry = $frameworkRegistry;
        $this->_helperCatalog = $helperCatalog;
        $this->_hlp = $helperData;
        $this->_prodHlp = $dropshipVendorProductHelperData;
        $this->_prodHlpForm = $helperForm;

        parent::__construct($context, $data);
    }

    protected $_form;
    protected $_product;
    protected $_oldStoreId;
    protected $_unregUrlStore;
    protected $_oldFieldsetRenderer;
    protected $_oldFieldsetElementRenderer;

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        if (!$this->_coreRegistry->registry('url_store')) {
            $this->_unregUrlStore = true;
            $this->_coreRegistry->register('url_store', $this->_storeManager->getStore());
        }
        $this->_oldStoreId = $this->_storeManager->getStore()->getId();
        $this->_storeManager->setCurrentStore(0);

        $this->_oldFieldsetRenderer = DataForm::getFieldsetRenderer();
        $this->_oldFieldsetElementRenderer = DataForm::getFieldsetElementRenderer();
        DataForm::setFieldsetRenderer(
            $this->getLayout()->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\Fieldset')
        );
        DataForm::setFieldsetElementRenderer(
            $this->getLayout()->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\FieldsetElement')
        );

        //$this->_helperCatalog->setDesignStore(0, 'adminhtml');

        return $this;
    }

    protected function _getUrlModelClass()
    {
        return 'core/url';
    }
    public function getUrl($route = '', $params = [])
    {
        if (!isset($params['_store']) && $this->_oldStoreId) {
            $params['_store'] = $this->_oldStoreId;
        }
        return parent::getUrl($route, $params);
    }

    protected function _afterToHtml($html)
    {
        /*
        DataForm::setFieldsetRenderer(
            $this->_oldFieldsetRenderer
        );
        DataForm::setFieldsetElementRenderer(
            $this->_oldFieldsetElementRenderer
        );
        */
        if ($this->_unregUrlStore) {
            $this->_unregUrlStore = false;
            $this->_coreRegistry->unregister('url_store');
        }
        $this->_storeManager->setCurrentStore($this->_oldStoreId);
        //$this->_helperCatalog->setDesignStore();
        return parent::_afterToHtml($html);
    }

    public function getPidBySku($sku)
    {
        return $this->_helperCatalog->getPidBySku($sku);
    }

    public function getVendor()
    {
        return ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendor();
    }

    public function getProductVendor($product=null)
    {
        if (null === $product) {
            $product = $this->getProduct();
        }
        return $this->_hlp->getVendor($product->getUdropshipVendor());
    }

    public function isMyProduct($product=null)
    {
        $product = $product ? $product : $this->getProduct();
        return !$product->getId()
            || $this->getVendor()->getId() == $this->getProductVendor($product)->getId();
    }

    public function getProduct()
    {
        if (null === $this->_product) {
            $this->_product = $this->_prodHlp->initProductEdit([
                'id' => $this->_request->getParam('id'),
                'vendor' => $this->getVendor()
            ]);
            $this->_coreRegistry->register('current_product', $this->_product);
            $this->_coreRegistry->register('product', $this->_product);
        }
        return $this->_product;
    }

    public function isQty($product=null)
    {
        if (null === $product) {
            $product = $this->getProduct();
        }
        return $this->_prodHlp->isQty($product);
    }

    protected function _addConfigurableSettings($prod, &$values)
    {
        $cfgFieldset = $this->_form->addFieldset('configurable',
            [
                'legend'=>__('Add Product Options'),
                'class'=>'fieldset-wide',
        ]);
        $this->addAdditionalElementType(
            'cfg_quick_create',
            '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form\QuickCreate'
        );
        $this->_addElementTypes($cfgFieldset);

        $cfgAttributes = $prod->getTypeInstance(true)
            ->getSetAttributes($prod);

        if ($prod->getId()) {
            $values['_cfg_attribute']['simple_skus'] = implode("\n",
                $this->_helperCatalog->getCfgSimpleSkus($prod->getId())
            );
            $values['_cfg_attribute']['attributes'] = @array_combine(
                $prod->getTypeInstance(true)->getUsedProductAttributeIds($prod),
                $prod->getTypeInstance(true)->getUsedProductAttributeIds($prod)
            );
        }

        $cfgQcEl = $cfgFieldset->addField('_cfg_quick_create', 'cfg_quick_create', [
            'name'      => '_cfg_attribute[quick_create]',
            'label'     => __('Simples Management'),
            'value_filter' => new Sprintf('%s', 2),
            'product' => $prod,
            'used_product_attributes' => $this->_prodHlp->getTplConfigurableAttributes(
                    $this->getVendor(),
                    $prod
                )
        ]);
        $cfgQcEl->setProduct($prod);
        $cfgFieldset->setProduct($prod);
        $cfgFieldset->setRenderer($this->getLayout()->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\QuickCreateFieldset'));
        $cfgFieldset->getRenderer()->setProduct($prod);
        $this->_prepareFieldsetColumns($cfgFieldset);
    }

    protected function _addGroupedAssocProducts($prod, &$values)
    {
        $coFieldset = $this->_form->addFieldset('grouped_assoc_products',
            [
                'legend'=>__('Associated Products'),
                'class'=>'fieldset-wide',
            ]);
        $this->addAdditionalElementType(
            'grouped_assoc_products',
            Mage::getConfig()->getBlockClassName('udprod/vendor_product_form_groupedAssocProducts')
        );
        $this->_addElementTypes($coFieldset);

        $coEl = $coFieldset->addField('_grouped_assoc_products', 'grouped_assoc_products', [
            'name'      => 'options',
            'label'     => __('Associated Products'),
            'value_filter' => new Sprintf('%s', 2),
            'product' => $prod,
            'is_top'=>true,
        ]);
        $coEl->setProduct($prod);
        $coFieldset->setProduct($prod);
        $coFieldset->setRenderer($this->getLayout()->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\Groupedassocproductsfieldset'));
        $coFieldset->getRenderer()->setProduct($prod);
        $this->_prepareFieldsetColumns($coFieldset);
    }

    protected function _addCustomOptions($prod, &$values)
    {
        $coFieldset = $this->_form->addFieldset('custom_options',
            [
                'legend'=>__('Custom Options'),
                'class'=>'fieldset-wide',
            ]);
        $this->addAdditionalElementType(
            'custom_options',
            Mage::getConfig()->getBlockClassName('udprod/vendor_product_form_customOptions')
        );
        $this->_addElementTypes($coFieldset);

        $coEl = $coFieldset->addField('_custom_options', 'custom_options', [
            'name'      => 'options',
            'label'     => __('Custom Options Management'),
            'value_filter' => new Sprintf('%s', 2),
            'product' => $prod,
            'is_top'=>true,
        ]);
        $coEl->setProduct($prod);
        $coFieldset->setProduct($prod);
        $coFieldset->setRenderer($this->getLayout()->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\CustomOptionsFieldset'));
        $coFieldset->getRenderer()->setProduct($prod);
        $this->_prepareFieldsetColumns($coFieldset);
    }

    protected function _addDownloadableOptions($prod, &$values)
    {
        $coFieldset = $this->_form->addFieldset('downloadable_options',
            [
                'legend'=>__('Downloadable Options'),
                'class'=>'fieldset-wide',
            ]);
        $this->addAdditionalElementType(
            'downloadable_options',
            '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form\Downloadable'
        );
        $this->_addElementTypes($coFieldset);

        $coEl = $coFieldset->addField('_downloadable_options', 'downloadable_options', [
            'name'      => 'options',
            'label'     => __('Downloadable Options Management'),
            'value_filter' => new Sprintf('%s', 2),
            'product' => $prod,
            'is_top'=>true,
        ]);
        $coEl->setProduct($prod);
        $coFieldset->setProduct($prod);
        $coFieldset->setRenderer($this->getLayout()->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\DownloadableFieldset'));
        $coFieldset->getRenderer()->setProduct($prod);
        $this->_prepareFieldsetColumns($coFieldset);
    }

    protected function _getWebsiteValues()
    {
        return $this->_helperCatalog->getWebsiteValues();
    }

    protected function _getCategoryValues()
    {
        return $this->_helperCatalog->getCategoryValues();
    }

    protected function _getStockItemField($field, $values)
    {
        return $this->_prodHlpForm->getStockItemField($field, $values);
    }
    protected function _getAttributeField($attribute)
    {
        return $this->_prodHlpForm->getAttributeField($attribute);
    }
    protected function _getUdmultiField($field, $mvData)
    {
        return $this->_prodHlpForm->getUdmultiField($field, $mvData);
    }
    protected function _getSystemField($field, $values)
    {
        return $this->_prodHlpForm->getSystemField($field, $values);
    }

    public function getForm()
    {
        if (null === $this->_form) {
            $prod = $this->getProduct();
            
            $hideFields = $this->_prodHlp->getHideEditFields();

            /** @var \Magento\Framework\Data\FormFactory $formFactory */
            $formFactory = $this->_hlp->getObj('\Magento\Framework\Data\FormFactory');
            $this->_form = $formFactory->create();
            $this->_form->setDataObject($prod);

            $values = $prod->getData();

            if ($this->_hlp->getStockItem($prod)) {
                $values = array_merge($values, ['stock_data'=>$this->_hlp->getStockItem($prod)->getData()]);
            }
            if (($udFormData = $this->_hlp->session()->getUdprodFormData(true))
                && is_array($udFormData)
            ) {
                unset($udFormData['media_gallery']);
                $values = array_merge($values, $udFormData);
            }

            $mvData = [];
            $v = $this->getVendor();
            if (!empty($values['udmulti'])) {
                $mvData = $values['udmulti'];
            } else {
                if ($this->_hlp->isUdmultiActive()) {
                    $this->_hlp->getObj('Unirgy\DropshipMulti\Helper\Data')->attachMultivendorData([$prod], false, true);
                    $mvData = $prod->getAllMultiVendorData($v->getId());
                    $mvData = !empty($mvData) ? $mvData : [];
                }
            }

            $cId = $prod->getCategoryIds();
            if (empty($cId) && !$this->_prodHlp->getUseTplProdCategoryBySetId($prod)) {
                $cId = $this->_prodHlp->getDefaultCategoryBySetId($prod);
            }
            $values['product_categories'] = @implode(',', (array)$cId);

            $wId = $prod->getWebsiteIds();
            if (empty($wId) && !$this->_prodHlp->getUseTplProdWebsiteBySetId($prod)) {
                $wId = $this->_prodHlp->getDefaultWebsiteBySetId($prod);
            }
            $values['product_websites'] = @implode(',', (array)$wId);

            $fsIdx = 0;
            $skipInputType = ['media_image'];
            if ('configurable' == $prod->getTypeId()) {
                $skipInputType[] = 'gallery';
            }
            $fieldsetsConfig = $this->_hlp->getScopeConfig('udprod/form/fieldsets');
            if (!is_array($fieldsetsConfig)) {
                $fieldsetsConfig = $this->_hlp->unserialize($fieldsetsConfig);
            }
            $_attributes = $prod->getAttributes();
            $attributes = [];
            foreach ($_attributes as $_attr) {
                $attributes[$_attr->getAttributeCode()] = $_attr;
            }
            $includedFields = [];
            $emptyForm = true;
            if (is_array($fieldsetsConfig)) {
            foreach ($fieldsetsConfig as $fsConfig) {
            if (is_array($fsConfig)) {
                $fields = [];

                foreach (['top_columns','bottom_columns','left_columns','right_columns'] as $colKey) {
                if (isset($fsConfig[$colKey]) && is_array($fsConfig[$colKey])) {
                    $requiredFields = (array)@$fsConfig['required_fields'];
                    foreach ($fsConfig[$colKey] as $fieldCode) {
                        if (!$this->_isFieldApplicable($prod, $fieldCode, $fsConfig)) continue;
                        $field = [];
                        if (strpos($fieldCode, 'product.') === 0
                            && !in_array(substr($fieldCode, 8), $hideFields)
                            && isset($attributes[substr($fieldCode, 8)])
                            && $this->isMyProduct()
                        ) {
                            if (($field = $this->_getAttributeField($attributes[substr($fieldCode, 8)]))) {
                                $field['product_attribute'] = $attributes[substr($fieldCode, 8)];
                            }
                        } elseif (strpos($fieldCode, 'udmulti.') === 0) {
                            $field = $this->_getUdmultiField(substr($fieldCode, 8), $mvData);
                        } elseif (strpos($fieldCode, 'stock_data.') === 0) {
                            $field = $this->_getStockItemField(substr($fieldCode, 11), $values);
                        } elseif (strpos($fieldCode, 'system.') === 0) {
                            $field = $this->_getSystemField(substr($fieldCode, 7), $values);
                        }
                        if (!empty($field) && !in_array($field['type'], $skipInputType)) {
                            switch ($colKey) {
                                case 'top_columns':
                                    $field['is_top'] = true;
                                    break;
                                case 'bottom_columns':
                                    $field['is_bottom'] = true;
                                    break;
                                case 'right_columns':
                                    $field['is_right'] = true;
                                    break;
                                default:
                                    $field['is_left'] = true;
                                    break;
                            }
                            if (in_array($fieldCode, $requiredFields)) {
                                $field['required'] = true;
                            } else {
                                $field['required'] = false;
                                if (!empty($field['class'])) {
                                    $field['class'] = str_replace('required-entry', '', $field['class']);
                                }
                            }
                            if (in_array($field['name'], $includedFields)) continue;
                            $includedFields[] = $field['name'];
                            $fields[] = $field;
                        }
                    }
                }}

                if (!empty($fields)) {
                    $fsIdx++;
                    $fieldset = $this->_form->addFieldset('group_fields'.$fsIdx,
                        [
                            'legend'=>$fsConfig['title'],
                            'class'=>'fieldset-wide',
                    ]);
                    $this->_addElementTypes($fieldset);
                    foreach ($fields as $field) {
                        if (!empty($field['input_renderer']) && !$this->_hasCustomInputType($field['type'])) {
                            $fieldset->addType($field['type'], $field['input_renderer']);
                        }
                        $formField = $fieldset->addField($field['id'], $field['type'], $field);
                        if (!empty($field['renderer'])) {
                            $formField->setRenderer($field['renderer']);
                        }
                    }
                    $this->_prepareFieldsetColumns($fieldset);
                    $emptyForm = false;
                }
            }}}

            if (!$prod->getId()) {
                foreach ($attributes as $attribute) {
                    if (!isset($values[$attribute->getAttributeCode()])) {
                        $values[$attribute->getAttributeCode()] = $attribute->getDefaultValue();
                    }
                }
            }

            if (!$emptyForm) {
                if ('configurable' == $prod->getTypeId()) {
                    $this->_addConfigurableSettings($prod, $values);
                }
                if (1 || 'configurable' != $prod->getTypeId()
                    || $this->_hlp->getScopeFlag('udprod/general/cfg_show_media_gallery')
                ) {
                    $cfgHideEditFields = explode(',', $this->_hlp->getScopeConfig('udropship/microsite/hide_product_attributes'));
                    if (isset($attributes['media_gallery'])
                        && !in_array('media_gallery', $cfgHideEditFields)
                        && $this->isMyProduct()
                    ) {
                        $attribute = $attributes['media_gallery'];
                        if ($attribute && (!$attribute->hasIsVisible() || $attribute->getIsVisible())
                            && ($inputType = $attribute->getFrontend()->getInputType())
                        ) {
                            $fieldset = $this->_form->addFieldset('group_fields_images',
                                [
                                    'legend'=>__('Images'),
                                    'class'=>'fieldset-wide',
                            ]);
                            $this->_addElementTypes($fieldset);
                            $fieldType      = $inputType;
                            $rendererClass  = $attribute->getFrontend()->getInputRendererClass();
                            if (!empty($rendererClass)) {
                                $fieldType  = $inputType . '_' . $attribute->getAttributeCode();
                                $fieldset->addType($fieldType, $rendererClass);
                            }

                            $mediaField = $fieldset->addField($attribute->getAttributeCode(), $fieldType,
                                [
                                    'name'      => $attribute->getAttributeCode(),
                                    'label'     => $attribute->getFrontend()->getLabel(),
                                    'class'     => $attribute->getFrontend()->getClass(),
                                    'required'  => $attribute->getIsRequired(),
                                    'note'      => $attribute->getNote(),
                                    'is_top'    => true
                                ]
                            )
                            ->setExplicitSection(true)
                            ->setEntityAttribute($attribute);
                            $this->_prepareFieldsetColumns($fieldset);
                        }
                    }
                }
                if ($this->_hlp->getScopeFlag('udprod/general/allow_custom_options')) {
                    //$this->_addCustomOptions($prod, $values);
                }
                if ('downloadable' == $prod->getTypeId()) {
                    //$this->_addDownloadableOptions($prod, $values);
                }
                if ('grouped' == $prod->getTypeId()) {
                    //$this->_addGroupedAssocProducts($prod, $values);
                }
            }

            $this->_form->addValues($values);

            $this->_form->setFieldNameSuffix('product');
        }
        return $this->_form;
    }

    protected function _hasCustomInputType($fieldCode)
    {
        return in_array($fieldCode, ['weight_weight', 'select_status', 'media_image_image']);
    }

    protected function _isFieldApplicable($prod, $fieldCode, $fsConfig)
    {
        $result = true;
        $ult = @$fsConfig['fields_extra'][$fieldCode]['use_limit_type'];
        $lt = @$fsConfig['fields_extra'][$fieldCode]['limit_type'];
        if (!is_array($lt)) {
            $lt = explode(',', $lt);
        }
        if ($ult && !in_array($prod->getTypeId(), $lt)) {
            $result = false;
        }
        if (strpos($fieldCode, 'udmulti.') === 0
            && !$this->_hlp->isUdmultiActive()
        ) {
            $result = false;
        }
        if (strpos($fieldCode, 'stock_data.') === 0
            && $this->_hlp->isUdmultiActive()
        ) {
            $result = false;
        }
        return $result;
    }

    protected function _prepareFieldsetColumns($fieldset)
    {
        $elements = $fieldset->getElements()->getIterator();
        reset($elements);
        $bottomElements = $topElements = $lcElements = $rcElements = [];
        while($element=current($elements)) {
            if ($element->getIsBottom()) {
                $bottomElements[] = $element->getId();
            } elseif ($element->getIsTop()) {
                $topElements[] = $element->getId();
            } elseif ($element->getIsRight()) {
                $rcElements[] = $element->getId();
            } else {
                $lcElements[] = $element->getId();
            }
            next($elements);
        }
        $fieldset->setTopColumn($topElements);
        $fieldset->setBottomColumn($bottomElements);
        $fieldset->setLeftColumn($lcElements);
        $fieldset->setRightColumn($rcElements);
        reset($elements);
        return $this;
    }

    protected $_additionalElementTypes = null;
    protected function _initAdditionalElementTypes()
    {
        if (is_null($this->_additionalElementTypes)) {
            $result = [
                'select_status'   => '\Magento\Framework\Data\Form\Element\Select',
                'stock_data_qty'=> '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form\StockDataQty',
                'tier_price'=> '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form\TierPrice',
                'group_price'=> '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form\GroupPrice',
                'price'    => '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form\Price',
                'weight_weight'   => '\Magento\Framework\Data\Form\Element\Text',
                'gallery'  => '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form\Gallery',
                'image'    => '\Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Image',
                'boolean'  => '\Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Boolean',
                //'textarea' => Mage::getConfig()->getBlockClassName('udprod/vendor_product_wysiwyg'),
                'product_categories' => '\Unirgy\Dropship\Block\CategoriesField',
                'media_image_image' => '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form\BaseImage'
            ];

            $events = ['adminhtml_catalog_product_edit_element_types', 'udprod_product_edit_element_types'];
            foreach ($events as $event) {
                $response = new DataObject();
                $response->setTypes([]);
                $this->_eventManager->dispatch($event, ['response'=>$response]);
                foreach ($response->getTypes() as $typeName=>$typeClass) {
                    $result[$typeName] = $typeClass;
                }
            }

            $this->_additionalElementTypes = $result;
        }
        return $this;
    }

    protected function _getAdditionalElementTypes()
    {
        $this->_initAdditionalElementTypes();
        return $this->_additionalElementTypes;
    }
    public function addAdditionalElementType($code, $class)
    {
        $this->_initAdditionalElementTypes();
        $this->_additionalElementTypes[$code] = $class;
        return $this;
    }

    protected function _addElementTypes(AbstractForm $baseElement)
    {
        $types = $this->_getAdditionalElementTypes();
        foreach ($types as $code => $className) {
            $baseElement->addType($code, $className);
        }
    }

}