<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Shipping;



class NewAction extends AbstractShipping
{
    public function execute()
    {
        $resultForward = $this->resultForwardFactory->create();
        $resultForward->forward('edit');
        return $resultForward;
    }
}
