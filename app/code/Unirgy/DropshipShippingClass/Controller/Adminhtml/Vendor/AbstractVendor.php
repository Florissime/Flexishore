<?php

namespace Unirgy\DropshipShippingClass\Controller\Adminhtml\Vendor;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

abstract class AbstractVendor extends Action
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Unirgy_DropshipShippingClass::shipclass_vendor');
    }

    /**
     * @return Page
     */
    protected function _initAction()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu("Unirgy_DropshipShippingClass::udshipclass_vendor")
            ->addBreadcrumb(__('Sales'), __('Sales'))
            ->addBreadcrumb(__('Dropship'), __('Dropship'))
            ->addBreadcrumb(__('Vendor Ship Classes'), __('Vendor Ship Classes'));
        return $resultPage;
    }
}
