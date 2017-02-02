<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Shipping;

use \Unirgy\Dropship\Model\Shipping;

class Delete extends AbstractShipping
{
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if(($id = $this->getRequest()->getParam('id')) > 0 ) {
            try {
                $model = $this->_hlp->createObj('\Unirgy\Dropship\Model\Shipping');
                /* @var $model Shipping */
                $model->setId($id)->delete();
                $this->messageManager->addSuccess(__('Shipping Method was successfully deleted'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
