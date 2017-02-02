<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\Controller\Result\RawFactory;
use \Magento\Framework\View\LayoutFactory;

class AdjustmentGrid extends AbstractStatement
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()
            ->getBlock('udropship.vendor.statement.adjustment')
            ->setStatementId($this->getRequest()->getParam('id'));
        $this->_view->renderLayout();
    }
}
