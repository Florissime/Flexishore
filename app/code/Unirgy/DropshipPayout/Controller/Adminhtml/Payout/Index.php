<?php

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

class Index extends AbstractPayout
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        return $resultPage;
    }
}
