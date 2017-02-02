<?php

namespace Unirgy\Dropship\Controller\Vendor;



class Batches extends AbstractVendor
{
    public function execute()
    {
        return $this->_renderPage(null, 'batches');
    }
}
