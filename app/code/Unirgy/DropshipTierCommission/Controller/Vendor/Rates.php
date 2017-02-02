<?php

namespace Unirgy\DropshipTierCommission\Controller\Vendor;



class Rates extends \Unirgy\Dropship\Controller\Vendor\AbstractVendor
{
    public function execute()
    {
        $this->_renderPage(null, 'tiercom_rates');
    }
}
