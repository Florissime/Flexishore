<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class SimpleCondRates
 * @package Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Form
 */
class SimpleCondRates extends AbstractElement
{
    /**
     * @return string
     */
    public function getElementHtml()
    {
        return '<div id="'.$this->getHtmlId().'_container"></div>';
    }
}
