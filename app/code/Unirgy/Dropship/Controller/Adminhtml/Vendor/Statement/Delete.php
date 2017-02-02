<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

use \Unirgy\Dropship\Model\Vendor\Statement;

class Delete extends AbstractStatement
{
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if (($id = $this->getRequest()->getParam('id')) > 0 ) {
            try {
                /** @var \Unirgy\Dropship\Model\Vendor\Statement $model */
                $model = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor\Statement');
                $model->setId($id)->delete();
                $this->messageManager->addSuccess(__('Statement was successfully deleted'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        return $resultRedirect->setPath('*/*/');
    }

}
