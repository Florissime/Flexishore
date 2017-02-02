<?php

namespace Unirgy\DropshipMicrosite\Controller\Vendor;



class RegisterSuccess extends AbstractVendor
{
    public function execute()
    {
        $this->_renderPage(null, 'register');
    }
}
