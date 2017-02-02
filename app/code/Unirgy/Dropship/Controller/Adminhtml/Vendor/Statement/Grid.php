<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

class Grid extends AbstractStatement
{
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}
