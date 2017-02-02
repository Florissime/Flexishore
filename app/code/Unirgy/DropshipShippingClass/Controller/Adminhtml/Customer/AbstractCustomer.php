<?php

namespace Unirgy\DropshipShippingClass\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

abstract class AbstractCustomer extends Action
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Unirgy_DropshipShippingClass::shipclass_customer');
    }

    /**
     * @return Page
     */
    protected function _initAction()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu("Unirgy_DropshipShippingClass::udshipclass_customer")
            ->addBreadcrumb(__('Sales'),__('Sales'))
            ->addBreadcrumb(__('Dropship'),__('Dropship'))
            ->addBreadcrumb(__('Customer Ship Classes'),__('Customer Ship Classes'));
        return $resultPage;
    }
}
