<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Shipping;

class Edit extends AbstractShipping
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        $id = $this->getRequest()->getParam('id');
        $resultPage->addBreadcrumb(
            $id ? __('Edit Shipping Method') : __('New Shipping Method'),
            $id ? __('Edit Shipping Method') : __('New Shipping Method')
        );
        $resultPage->getConfig()->getTitle()->prepend(
            $id ? __('Edit Shipping Method') : __('New Shipping Method')
        );
        return $resultPage;
    }
}
