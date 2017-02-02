<?php

namespace Unirgy\DropshipTierShipping\Controller\Vendor;

/**
 * Class V2rates
 * @package Unirgy\DropshipTierShipping\Controller\Vendor
 */
class V2rates extends AbstractVendor
{
    /**
     *
     */
    public function execute()
    {
        $this->_renderPage(null, 'tiership_v2_rates');
    }
}
