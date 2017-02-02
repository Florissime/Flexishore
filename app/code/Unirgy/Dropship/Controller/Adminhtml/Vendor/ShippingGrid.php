<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\Controller\Result\RawFactory;
use \Magento\Framework\View\LayoutFactory;

class ShippingGrid extends AbstractVendor
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()
            ->getBlock('udropship.vendor.shippinggrid')
            ->setVendorId($this->getRequest()->getParam('id'));
        $this->_view->renderLayout();
    }
}
