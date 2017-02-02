<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

class PayoutGrid extends AbstractStatement
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()
            ->getBlock('udropship.vendor.statement.payoutgrid')
            ->setStatementId($this->getRequest()->getParam('id'));
        $this->_view->renderLayout();
    }
}
