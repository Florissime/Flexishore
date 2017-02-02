<?php

namespace Unirgy\DropshipMicrosite\Controller\Adminhtml\Registration;

class Grid extends AbstractRegistration
{
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}
