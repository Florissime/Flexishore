<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Shipping;

class Grid extends AbstractShipping
{
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}
