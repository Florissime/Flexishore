<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;

class Grid extends AbstractVendor
{
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}
