<?php
namespace Unirgy\DropshipShippingClass\Controller\Adminhtml\Vendor;

class NewAction extends AbstractVendor
{
    public function execute()
    {
        $this->_forward('edit');
    }
}
