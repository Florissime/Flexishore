<?php

namespace Unirgy\DropshipVendorProduct\Controller\Vendor;



class Index extends AbstractVendor
{
    public function execute()
    {
        $this->_forward('products');
    }
}
