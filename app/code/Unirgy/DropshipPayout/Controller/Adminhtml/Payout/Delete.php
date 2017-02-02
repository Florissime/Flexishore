<?php

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

class Delete extends AbstractPayout
{
    public function execute()
    {
        if (($id = $this->getRequest()->getParam('id')) > 0 ) {
            try {
                $model = $this->_payoutFactory->create();
                /* @var $model \Unirgy\DropshipPayout\Model\Payout */
                $model->load($id)->delete();
                $this->messageManager->addSuccess(__('Payout was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (\Exception $e) {
                $this->_hlp->logError($e);
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            }
        }
        $this->_redirect('*/*/');
    }
}
