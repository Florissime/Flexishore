<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

class RowGrid extends AbstractStatement
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()
            ->getBlock('udropship.vendor.statement.rowgrid')
            ->setStatementId($this->getRequest()->getParam('id'));
        $this->_view->renderLayout();
    }
}
