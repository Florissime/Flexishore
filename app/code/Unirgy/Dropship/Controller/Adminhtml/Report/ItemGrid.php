<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Report;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\Controller\Result\RawFactory;
use \Magento\Framework\View\LayoutFactory;

class ItemGrid extends AbstractReport
{
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}
