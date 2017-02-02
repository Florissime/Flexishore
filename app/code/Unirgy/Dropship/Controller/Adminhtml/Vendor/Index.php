<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;

use \Unirgy\Dropship\Helper\ProtectedCode;

class Index extends AbstractVendor
{
    public function execute()
    {
        try {
            ProtectedCode::validateLicense('Unirgy_Dropship');
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(__('Vendors'), __('Vendors'));
        return $resultPage;
    }
}
