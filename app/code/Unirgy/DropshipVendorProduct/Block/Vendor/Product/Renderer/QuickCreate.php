<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Form as DataForm;
use Magento\Framework\Data\Form\AbstractForm;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipVendorProduct\Helper\Data as HelperData;
use Unirgy\DropshipVendorProduct\Helper\Form;
use Unirgy\DropshipVendorProduct\Model\Source;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;
use Unirgy\Dropship\Model\Source as DropshipModelSource;

class QuickCreate extends Template implements RendererInterface
{
    /**
     * @var Source
     */
    protected $_modelSource;

    /**
     * @var DropshipModelSource
     */
    protected $_src;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    /**
     * @var Form
     */
    protected $_helperForm;

    protected $_element = null;

    public function __construct(
        Context $context,
        Source $modelSource,
        DropshipModelSource $dropshipModelSource,
        HelperData $helperData, 
        DropshipHelperData $dropshipHelperData, 
        Form $helperForm,
        array $data = [])
    {
        $this->_modelSource = $modelSource;
        $this->_src = $dropshipModelSource;
        $this->_helperData = $helperData;
        $this->_hlp = $dropshipHelperData;
        $this->_helperForm = $helperForm;
        parent::__construct($context, $data);
        $this->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/vendor/product/renderer/cfg_quick_create.phtml');
    }

    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        $html = $this->toHtml();
        return $html;
    }

    public function setElement(AbstractElement $element)
    {
        $this->_element = $element;
        return $this;
    }

    public function getElement()
    {
        return $this->_element;
    }

    protected function _prepareLayout()
    {
        $this->setChild('delete_button',
            $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData([
                    'label' => __('Delete'),
                    'class' => 'delete delete-option'
                ]));
        $this->setChild('add_button',
            $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData([
                    'label' => __('Add'),
                    'class' => 'add',
                    'id'    => 'add_new_option_button'
                ]));
        return parent::_prepareLayout();
    }

    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }

    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_button');
    }

    public function getStockStatusOptions()
    {
        return $this->_modelSource->setPath('stock_status')->toOptionHash(true);
    }
    public function getSystemStatusOptions()
    {
        return $this->_modelSource->setPath('system_status')->toOptionHash(true);
    }
    public function getUdmultiStatusOptions()
    {
        return $this->_hlp->getObj('Unirgy\DropshipMultiPrice\Model\Source')->setPath('vendor_product_status')->toOptionHash(true);
    }
    public function getUdmultiStateOptions()
    {
        return $this->_hlp->getObj('\Unirgy\DropshipMultiPrice\Model\Source')->setPath('vendor_product_state')->toOptionHash(true);
    }

    public function getYesnoOptions()
    {
        return $this->_src->setPath('yesno')->toOptionHash(true);
    }

    public function getCfgAttributeLabels()
    {
        $cfgAttrs = $this->getFirstAttributes();
        $tuple = $this->getCfgAttributeValueTuple();
        $labels = [];
        foreach ($cfgAttrs as $__i => $__ca) {
            $labels[] = $__ca->getSource()->getOptionText($tuple[$__i]);
        }
        return $labels;
    }
    public function getCfgAttributeLabel()
    {
        return $this->getFirstAttribute()->getSource()->getOptionText($this->getCfgAttributeValue());
    }
    public function getCfgAttributeCode()
    {
        return $this->getCfgAttribute()->getAttributeCode();
    }
    public function getCfgAttribute()
    {
        return $this->getFirstAttribute();
    }
    public function getConfigurableAttributes($skipFirst=false)
    {
        $cfgAttrs = $this->_helperData->getConfigurableAttributes($this->getProduct(), !$this->getProduct()->getId());
        if ($skipFirst) {
            $firstAttr = $this->getFirstAttributes();
            $firstCnt = count($firstAttr);
            while (--$firstCnt>=0) array_shift($cfgAttrs);
        }
        return $cfgAttrs;
    }
    public function getFirstAttributes()
    {
        return $this->_helperData->getCfgFirstAttributes($this->getProduct());
    }
    public function getFirstAttribute()
    {
        return $this->_helperData->getCfgFirstAttribute($this->getProduct());
    }
    public function getFirstAttributesValues($used=null, $filters=[], $filterFlag=true)
    {
        $values = [];
        $attrs = $this->getFirstAttributes();
        foreach ($attrs as $attr) {
            $values[] = $this->getAttributeValues($attr, $used, $filters, $filterFlag);
        }
        return $values;
    }
    public function getFirstAttributeValues($used=null, $filters=[], $filterFlag=true)
    {
        return $this->getAttributeValues($this->getFirstAttribute(), $used, $filters, $filterFlag);
    }
    public function getAttributeValues($attribute, $used=null, $filters=[], $filterFlag=true)
    {
        return $this->_helperData->getCfgAttributeValues($this->getProduct(), $attribute, $used, $filters, $filterFlag);
    }

    protected $_product;
    public function setProduct($product)
    {
        $this->_product = $product;
        return $this;
    }
    public function getProduct()
    {
        return $this->_product;
    }

    public function getName()
    {
        $prod = $this->_element->getProduct();
        return $prod
            ? $this->_element->getProduct()->getName()
            : '';
    }

    public function getCfgData($key)
    {
        $prod = $this->_element->getProduct();
        return $prod
            ? $this->_element->getProduct()->getData($key)
            : '';
    }

    public function getVendor()
    {
        return ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendor();
    }

    public function getProductVendor()
    {
        return $this->_hlp->getVendor($this->getProduct()->getUdropshipVendor());
    }

    public function isMyProduct()
    {
        return !$this->getProduct()->getId()
            || $this->getVendor()->getId() == $this->getProductVendor()->getId();
    }

    public function getSimpleProducts($filtered=true)
    {
        $prod = $this->_element->getProduct();
        $cfgAttrs = $this->getFirstAttributes();
        $filter = [];
        $tuple = $this->getCfgAttributeValueTuple();
        foreach ($cfgAttrs as $__i => $__ca) {
            $filter[$__ca->getAttributeCode()] = $tuple[$__i];
        }
        return $prod ?
            ($filtered
                ? $this->_helperData->getFilteredSimpleProductData($prod, $filter)
                : $this->_helperData->getEditSimpleProductData($prod))
            : [];
    }

    protected $_galleryContent;
    public function getGalleryContent()
    {
        if (null === $this->_galleryContent) {
            $this->_galleryContent = $this->getLayout()->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Gallerycfgcontent');
            $this->_galleryContent->setCfgAttributes($this->getFirstAttributes());
            $this->_galleryContent->setCfgAttributeValueTuple($this->getCfgAttributeValueTuple());
            $this->_galleryContent->setForm($this->_element->getForm());
            $this->_galleryContent->setProduct($this->getProduct());
        }
        return $this->_galleryContent;
    }

    public function getGalleryContentHtml()
    {
        return $this->getGalleryContent()->toHtml();
    }

    public function isOneColumnCfgAttrs()
    {
        return 'one_column' == $this->_scopeConfig->getValue('udprod/quick_create_layout/cfg_attributes', ScopeInterface::SCOPE_STORE);
    }

    public function getCfgAttrsColumnTitle()
    {
        return $this->_scopeConfig->getValue('udprod/quick_create_layout/cfg_attributes_title', ScopeInterface::SCOPE_STORE);
    }

    protected $_columnsForm;
    public function getColumnsForm()
    {
        if (null !== $this->_columnsForm) {
            return $this->_columnsForm;
        }
        $htmlId = $this->_element->getId();
        $prod = $this->getProduct();
        $hideFields = $this->_helperData->getHideEditFields();
        $skipInputType = ['media_image'];
        if ('configurable' == $prod->getTypeId()) {
            $skipInputType[] = 'gallery';
        }
        $attributes = $this->_helperData->getQuickCreateAttributes();
        $fsIdx = 0;
        /** @var \Magento\Framework\Data\FormFactory $formFactory */
        $formFactory = $this->_hlp->getObj('\Magento\Framework\Data\FormFactory');
        $this->_columnsForm = $formFactory->create();
        $columnsConfig = $this->_scopeConfig->getValue('udprod/quick_create_layout/columns', ScopeInterface::SCOPE_STORE);
        if (!is_array($columnsConfig)) {
            $columnsConfig = $this->_hlp->unserialize($columnsConfig);
            if (is_array($columnsConfig)) {
            foreach ($columnsConfig as $fsConfig) {
            if (is_array($fsConfig)) {
                $fields = [];
                foreach (['columns'] as $colKey) {
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
                            $field = $this->_getAttributeField($attributes[substr($fieldCode, 8)]);
                        } elseif (strpos($fieldCode, 'udmulti.') === 0) {
                            $field = $this->_getUdmultiField(substr($fieldCode, 8), []);
                        } elseif (strpos($fieldCode, 'stock_data.') === 0) {
                            $field = $this->_getStockItemField(substr($fieldCode, 11), []);
                        }
                        if (!empty($field) && !in_array($field['type'], $skipInputType)) {
                            if (in_array($fieldCode, $requiredFields)) {
                                $field['required'] = true;
                            } else {
                                $field['required'] = false;
                                if (!empty($field['class'])) {
                                    $field['class'] = str_replace('required-entry', '', $field['class']);
                                }
                            }
                            $field['value'] = $this->prepareIdSuffix('$'.strtoupper($field['name']));
                            $field['id'] = $this->prepareIdSuffix($this->_columnsForm->addSuffixToName(
                                $field['name'],
                                $this->_element->getName().'[$ROW]'
                            ));
                            if (isset($field['class'])) {
                                $field['class'] = str_replace(
                                    'udmulti_special_date',
                                    $this->prepareIdSuffix($this->_columnsForm->addSuffixToName(
                                        'udmulti_special_date',
                                        'udsell_cfgsell[$ROW]'
                                    )),
                                    $field['class']
                                );
                            }
                            $fields[] = $field;
                        }
                    }
                }}

                if (!empty($fields)) {
                    $fsIdx++;
                    $fieldset = $this->_columnsForm->addFieldset('group_fields'.$fsIdx,
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
        }
        $this->_columnsForm->setDataObject($prod);
        $this->_columnsForm->addFieldNameSuffix($this->_element->getName().'[$ROW]');
        return $this->_columnsForm;
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

    public function prepareIdSuffix($id)
    {
        return preg_replace('/[^a-zA-Z0-9\$]/', '_', $id);
    }

    protected function _hasCustomInputType($fieldCode)
    {
        return in_array($fieldCode, ['weight_weight', 'select_status', 'media_image_image']);
    }

    protected function _isFieldApplicable($prod, $fieldCode, $fsConfig)
    {
        $result = true;
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

    protected function _getStockItemField($field, $values)
    {
        return $this->_helperForm->getStockItemField($field, $values);
    }
    protected function _getAttributeField($attribute)
    {
        return $this->_helperForm->getAttributeField($attribute);
    }
    protected function _getUdmultiField($field, $mvData)
    {
        return $this->_helperForm->getUdmultiField($field, $mvData);
    }

    protected $_additionalElementTypes = null;
    protected function _initAdditionalElementTypes()
    {
        if (is_null($this->_additionalElementTypes)) {
            $result = [
                'date'    => '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form\DateRaw',
                'select_status'   => '\Magento\Framework\Data\Form\Element\Select',
                'stock_data_qty'=> '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form\StockDataQty',
                'price_price'    => '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form\Price',
                'price'    => '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form\Price',
                'weight_weight'   => '\Magento\Framework\Data\Form\Element\Text',
                'gallery'  => '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form\Gallery',
                'image'    => '\Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Image',
                'boolean'  => '\Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Boolean',
                'textarea' => '\Magento\Catalog\Block\Adminhtml\Helper\Form\Wysiwyg'
            ];

            $response = new DataObject();
            $response->setTypes([]);
            $this->_eventManager->dispatch('adminhtml_catalog_product_edit_element_types', ['response'=>$response]);

            foreach ($response->getTypes() as $typeName=>$typeClass) {
                $result[$typeName] = $typeClass;
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