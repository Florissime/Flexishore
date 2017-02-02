<?php

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

class Grid extends AbstractPayout
{
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}
