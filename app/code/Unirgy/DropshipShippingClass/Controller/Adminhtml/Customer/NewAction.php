<?php
namespace Unirgy\DropshipShippingClass\Controller\Adminhtml\Customer;

class NewAction extends AbstractCustomer
{
    public function execute()
    {
        $this->_forward('edit');
    }
}
