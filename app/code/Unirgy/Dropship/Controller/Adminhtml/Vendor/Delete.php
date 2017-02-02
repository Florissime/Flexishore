<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;

use \Unirgy\Dropship\Model\Vendor;

class Delete extends AbstractVendor
{
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if (($id = $this->getRequest()->getParam('id')) > 0 ) {
            try {
                $model = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor');
                /* @var $model Vendor */
                $model->setId($id)->delete();
                $this->messageManager->addSuccess(__('Vendor was successfully deleted'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
