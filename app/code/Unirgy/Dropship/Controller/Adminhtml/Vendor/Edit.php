<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;

class Edit extends AbstractVendor
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        $id = $this->getRequest()->getParam('id');
        $resultPage->addBreadcrumb(
            $id ? __('Edit Vendor') : __('New Vendor'),
            $id ? __('Edit Vendor') : __('New Vendor')
        );
        $resultPage->getConfig()->getTitle()->prepend(
            $id ? __('Edit Vendor') : __('New Vendor')
        );
        if (($v = $this->_registry->registry('vendor_data')) && $v->getVendorName()) {
            $resultPage->getConfig()->getTitle()->prepend($v->getVendorName());
        }
        return $resultPage;
    }
}
