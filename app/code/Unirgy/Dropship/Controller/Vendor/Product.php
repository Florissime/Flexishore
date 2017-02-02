<?php

namespace Unirgy\Dropship\Controller\Vendor;



class Product extends AbstractVendor
{
    public function execute()
    {
        return $this->_renderPage(null, 'stockprice');
    }
}
