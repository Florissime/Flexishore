<?php

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

class Cancel extends AbstractPayout
{
    public function execute()
    {
        if (($id = $this->getRequest()->getParam('id')) > 0 ) {
            try {
                if (($id = $this->getRequest()->getParam('id')) > 0
                    && ($payout = $this->_payoutFactory->create()->load($id)) && $payout->getId()
                ) {
                    $payout->cancel();
                } else {
                    throw new \Exception(__("Payout '%1' no longer exists", $id));
                }
                $this->messageManager->addSuccess(__('Payout was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            }
        }
        $this->_redirect('*/*/');
    }
}
