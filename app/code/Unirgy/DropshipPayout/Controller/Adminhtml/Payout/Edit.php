<?php

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

class Edit extends AbstractPayout
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        $id = $this->getRequest()->getParam('id');
        $resultPage->addBreadcrumb(
            $id ? __('View Payout') : __('Create Payout'),
            $id ? __('View Payout') : __('Create Payout')
        );
        $resultPage->getConfig()->getTitle()->prepend(
            __('View Payout')
        );
        return $resultPage;
    }
}
