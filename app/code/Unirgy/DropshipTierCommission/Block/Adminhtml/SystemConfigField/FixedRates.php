<?php

namespace Unirgy\DropshipTierCommission\Block\Adminhtml\SystemConfigField;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreManagerInterface;

class FixedRates extends Field
{

    protected $_element = null;

    public function _construct()
    {
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipTierCommission::udtiercom/system/form_field/fixed/rates_config.phtml');
        }
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        $html = $this->_toHtml();
        return $html;
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
