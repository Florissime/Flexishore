<?php

namespace Unirgy\DropshipTierShipping\Controller\Vendor;

class Simplerates extends AbstractVendor
{
    public function execute()
    {
        $this->_renderPage(null, 'tiership_simple_rates');
    }
}
