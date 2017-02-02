<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;

class ProductGrid extends AbstractVendor
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()
            ->getBlock('udropship.vendor.productgrid')
            ->setVendorId($this->getRequest()->getParam('id'));
        $this->_view->renderLayout();
    }
}
