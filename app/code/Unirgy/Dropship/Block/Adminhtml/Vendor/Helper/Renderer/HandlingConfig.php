<?php

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Renderer;

use \Magento\Backend\Block\Widget;
use \Magento\Framework\Data\Form\Element\AbstractElement;
use \Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class HandlingConfig extends Widget implements RendererInterface
{
    protected $_element = null;

    public function _construct()
    {
        $this->setTemplate('Unirgy_Dropship::udropship/vendor/helper/handling_config.phtml');
    }

    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->toHtml();
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
                ->setData(array(
                    'label' => __('Delete'),
                    'class' => 'delete delete-option'
                )));
        $this->setChild('add_button',
            $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData(array(
                    'label' => __('Add'),
                    'class' => 'add',
                    'id'    => 'handling_config_add_new_option_button'
                )));
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

}