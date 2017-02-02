<?php

namespace Unirgy\DropshipTierShipping\Controller\Vendor;

class Rates extends AbstractVendor
{
    public function execute()
    {
        $this->_renderPage(null, 'tiership_rates');
    }
}
