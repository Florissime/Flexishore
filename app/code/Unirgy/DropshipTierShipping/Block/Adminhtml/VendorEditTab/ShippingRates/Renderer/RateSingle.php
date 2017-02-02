<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\Renderer;

use Magento\Framework\View\Element\Template;
use Unirgy\DropshipTierShipping\Block\Vendor\RateSingle as VendorRateSingle;

/**
 * Class RateSingle
 * @package Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\Renderer
 */
class RateSingle extends VendorRateSingle
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipTierShipping::udtiership/vendor/helper/rate_single.phtml');
        }
    }
}
