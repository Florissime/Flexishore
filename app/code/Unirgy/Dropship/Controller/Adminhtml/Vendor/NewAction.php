<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;



class NewAction extends AbstractVendor
{
    public function execute()
    {
        $resultForward = $this->resultForwardFactory->create();
        $resultForward->forward('edit');
        return $resultForward;
    }
}
