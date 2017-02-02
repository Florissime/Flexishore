<?php

namespace Unirgy\DropshipVendorProduct\Block\Adminhtml\SystemConfigField;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Layout;
use Unirgy\DropshipVendorProduct\Helper\Data as HelperData;

class QuickCreateConfig extends Field
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var Layout
     */
    protected $_viewLayout;


    protected $_element = null;

    public function __construct(Context $context, 
        HelperData $helperData, 
        Layout $viewLayout, 
        array $data = [])
    {
        $this->_helperData = $helperData;
        $this->_viewLayout = $viewLayout;

        parent::__construct($context, $data);
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipVendorProduct::udprod/system/form_field/quick_create_config.phtml');
        }
    }

    public function getElementHtml(AbstractElement $element)
    {
        return $this->_getElementHtml($element);
    }
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        $html = $this->_toHtml();
        return $html;
    }

    public function getQuickCreateFieldsConfig()
    {
        return $this->_helperData->getQuickCreateFieldsConfig();
    }

    public function getFieldName()
    {
        return $this->getData('field_name')
            ? $this->getData('field_name')
            : ($this->getElement() ? $this->getElement()->getName() : '');
    }

    protected $_idSuffix;
    public function resetIdSuffix()
    {
        $this->_idSuffix = null;
        return $this;
    }
    public function getIdSuffix()
    {
        if (null === $this->_idSuffix) {
            $this->_idSuffix = $this->prepareIdSuffix($this->getFieldName());
        }
        return $this->_idSuffix;
    }

    public function prepareIdSuffix($id)
    {
        return preg_replace('/[^a-zA-Z0-9\$]/', '_', $id);
    }

    public function suffixId($id)
    {
        return $id.$this->getIdSuffix();
    }

    public function getAddButtonId()
    {
        return $this->suffixId('addBtn');
    }

    public function getFieldContainerBlock($fieldName)
    {
        return $this->getLayout()->getBlockSingleton('\Unirgy\Dropship\Block\Adminhtml\SystemConfigFormField\FieldContainer')
            ->setTemplate('Unirgy_DropshipVendorProduct::udprod/system/form_field/quick_create_column_config.phtml')
            ->setFieldName($fieldName);
    }


}