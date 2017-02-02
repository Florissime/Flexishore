<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreManagerInterface;

class SimpleRates extends Field
{
    protected $_element = null;

    public function _construct() {
        parent::_construct();
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipTierShipping::udtiership/system/form_field/simple_rates_config.phtml');
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

    public function getColumnTitle($subkeyColumns, $idx)
    {
        reset($subkeyColumns);
        $i = 0;
        while ($i++ != $idx) next($subkeyColumns);
        $title = '';
        switch (current($subkeyColumns)) {
            case 'cost':
                $title = __('Cost for the first item');
                break;
            case 'additional':
                $title = __('Additional item cost');
                break;
        }
        return $title;
    }

}
