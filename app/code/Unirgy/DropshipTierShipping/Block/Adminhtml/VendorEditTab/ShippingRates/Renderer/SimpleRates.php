<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\Renderer;

use Magento\Backend\Block\Widget;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class SimpleRates
 * @package Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\Renderer
 */
class SimpleRates extends Widget implements RendererInterface
{

    /**
     * @var null
     */
    protected $_element = null;

    /**
     *
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('Unirgy_DropshipTierShipping::udtiership/vendor/helper/simple_rates_config.phtml');
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->toHtml();
    }

    /**
     * @param AbstractElement $element
     * @return $this
     */
    public function setElement(AbstractElement $element)
    {
        $this->_element = $element;
        return $this;
    }

    /**
     * @return null
     */
    public function getElement()
    {
        return $this->_element;
    }

    /**
     * @return array
     */
    public function getTiershipSimpleRates()
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

    /**
     * @return mixed
     */
    public function getGlobalTierShipConfigSimple()
    {
        $value = $this->_scopeConfig->getValue('carriers/udtiership/simple_rates',
                                               ScopeInterface::SCOPE_STORE);
        if (is_string($value)) {
            $value = unserialize($value);
        }
        return $value;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface|null
     */
    public function getStore()
    {
        return $this->_storeManager->getDefaultStoreView();
    }

    /**
     * @param $subkeyColumns
     * @param $idx
     * @return string
     */
    public function getColumnTitle($subkeyColumns, $idx)
    {
        reset($subkeyColumns);
        $i = 0;
        while ($i++ != $idx) next($subkeyColumns);
        $title = '';
        $column = current($subkeyColumns);
        switch ($column[1]) {
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
