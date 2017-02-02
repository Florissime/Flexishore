<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\Renderer;

use Magento\Framework\View\Element\Template;
use Unirgy\DropshipTierShipping\Block\Vendor\SimpleRateSingle as VendorSimpleRateSingle;

class SimpleRateSingle extends VendorSimpleRateSingle
{
    public function _construct()
    {
        parent::_construct();
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipTierShipping::udtiership/vendor/helper/simple_rate_single.phtml');
        }
    }
}
