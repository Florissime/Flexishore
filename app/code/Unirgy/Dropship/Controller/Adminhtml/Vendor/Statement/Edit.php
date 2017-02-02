<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\View\LayoutFactory;

class Edit extends AbstractStatement
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        return $resultPage;
    }
}
