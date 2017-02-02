<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

class RefundRowGrid extends AbstractStatement
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()
            ->getBlock('udropship.vendor.statement.refundrowgrid')
            ->setStatementId($this->getRequest()->getParam('id'));
        $this->_view->renderLayout();
    }
}
