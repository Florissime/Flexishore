<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

class Index extends AbstractStatement
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        return $resultPage;
    }
}
