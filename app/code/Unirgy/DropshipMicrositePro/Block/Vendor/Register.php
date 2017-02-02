<?php

namespace Unirgy\DropshipMicrositePro\Block\Vendor;

use Magento\Directory\Block\Data as BlockData;
use Magento\Directory\Helper\Data as HelperData;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\AbstractForm;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipMicrositePro\Helper\Data as DropshipMicrositeProHelperData;
use Unirgy\DropshipMicrositePro\Helper\ProtectedCode;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;

class Register extends BlockData
{
    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    /**
     * @var DropshipMicrositeProHelperData
     */
    protected $_mspHlp;

    /**
     * @var ProtectedCode
     */
    protected $_mspHlpPr;

    /** @var \Magento\Framework\Data\FormFactory  */
    protected $_formFactory;

    public function __construct(
        \Magento\Framework\Data\FormFactory $formFactory,
        DropshipHelperData $udropshipHelper,
        DropshipMicrositeProHelperData $micrositeProHelper,
        ProtectedCode $micrositeProProtected,
        Context $context,
        HelperData $directoryHelper, 
        EncoderInterface $jsonEncoder, 
        Config $configCacheType, 
        CollectionFactory $regionCollectionFactory, 
        CountryCollectionFactory $countryCollectionFactory, 
        array $data = []
    )
    {
        $this->_formFactory = $formFactory;
        $this->_hlp = $udropshipHelper;
        $this->_mspHlp = $micrositeProHelper;
        $this->_mspHlpPr = $micrositeProProtected;

        parent::__construct($context, $directoryHelper, $jsonEncoder, $configCacheType, $regionCollectionFactory, $countryCollectionFactory, $data);
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        Form::setFieldsetRenderer(
            $this->getLayout()->createBlock('Unirgy\DropshipMicrositePro\Block\Vendor\Register\Renderer\Fieldset')
        );
        Form::setFieldsetElementRenderer(
            $this->getLayout()->createBlock('Unirgy\DropshipMicrositePro\Block\Vendor\Register\Renderer\FieldsetElement')
        );

        $form = $this->_formFactory->create();
        $this->setForm($form);

        $fsIdx = 0;
        $columnsConfig = $this->_scopeConfig->getValue('udsignup/form/fieldsets', ScopeInterface::SCOPE_STORE);
        if (!is_array($columnsConfig)) {
            $columnsConfig = $this->_hlp->unserialize($columnsConfig);
            if (is_array($columnsConfig)) {
            foreach ($columnsConfig as $fsConfig) {
            if (is_array($fsConfig)) {
                $requiredFields = (array)@$fsConfig['required_fields'];
                $fieldsExtra = (array)@$fsConfig['fields_extra'];
                $fields = [];
                foreach (['top_columns','bottom_columns','left_columns','right_columns'] as $colKey) {
                if (isset($fsConfig[$colKey]) && is_array($fsConfig[$colKey])) {
                    foreach ($fsConfig[$colKey] as $fieldCode) {
                        $field = $this->_mspHlpPr->getRegistrationField($fieldCode);
                        if (!empty($field)) {
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
                            if (!empty($fieldsExtra[$fieldCode]['use_custom_label'])
                                && !empty($fieldsExtra[$fieldCode]['custom_label'])
                            ) {
                                $field['label'] = $fieldsExtra[$fieldCode]['custom_label'];
                            }
                            if (!empty($fieldsExtra[$fieldCode]['has_url'])) {
                                $field['label'] = str_replace('{{url}}', @$fieldsExtra[$fieldCode]['url'], $field['label']);
                            }
                            $fields[$fieldCode] = $field;
                        }
                    }
                }}

                if (!empty($fields)) {
                    $fsIdx++;
                    $fieldset = $form->addFieldset('group_fields'.$fsIdx,
                        [
                            'legend'=>$fsConfig['title'],
                            'class'=>'fieldset-wide',
                    ]);
                    $this->_addElementTypes($fieldset);
                    foreach ($fields as $field) {
                        if (!empty($field['input_renderer'])) {
                            $fieldset->addType($field['type'], $field['input_renderer']);
                        }
                        $formField = $fieldset->addField($field['id'], $field['type'], $field);
                        if (!empty($field['renderer'])) {
                            $formField->setRenderer($field['renderer']);
                        }
                        $formField->addClass('input-text');
                        if (!empty($field['required'])) {
                            $formField->addClass('required-entry');
                        }
                    }
                    $this->_prepareFieldsetColumns($fieldset);
                    $emptyForm = false;
                }
            }}}
        }

        $this->_eventManager->dispatch('udmspro_register_form', ['form'=>$form]);

        $_data = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getRegistrationFormData(true);
        if ($_data) {
            $form->setValues($_data);
        }

        return $this;
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
        $this->_additionalElementTypes = [
            'image' => '\Unirgy\DropshipMicrositePro\Block\Vendor\Register\Form\Image',
            'radios' => '\Unirgy\DropshipMicrositePro\Block\Vendor\Register\Form\Radios',
            'wysiwyg' => '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\Wysiwyg',
            'statement_po_type' => '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\StatementPoType',
            'payout_po_status_type' => '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\PayoutPoStatusType',
            'notify_lowstock' => '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\NotifyLowstock',
        ];
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