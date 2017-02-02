<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Shipping;

class Index extends AbstractShipping
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        return $resultPage;
    }
}
