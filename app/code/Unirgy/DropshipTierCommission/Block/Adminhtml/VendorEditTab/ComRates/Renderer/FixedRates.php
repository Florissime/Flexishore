<?php

namespace Unirgy\DropshipTierCommission\Block\Adminhtml\VendorEditTab\ComRates\Renderer;

use Magento\Backend\Block\Widget;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Store\Model\StoreManagerInterface;

class FixedRates extends Widget implements RendererInterface
{
    protected $_element = null;

    public function _construct()
    {
        $this->setTemplate('Unirgy_DropshipTierCommission::udtiercom/vendor/helper/fixed/rates_config.phtml');
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

    public function getTiercomFixedRates()
    {
        $value = $this->_element->getValue();
        if (is_string($value)) {
            $value = unserialize($value);
        }
        if (!is_array($value)) {
            $value = [];
        }
        return $value;
    }

    public function getStore()
    {
        return $this->_storeManager->getDefaultStoreView();
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
                                          'id' => 'udtcFixed_config_add_new_option_button'
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
}
