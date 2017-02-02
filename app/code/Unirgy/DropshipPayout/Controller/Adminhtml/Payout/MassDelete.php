<?php

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

use Magento\Backend\App\Action\Context;
use Psr\Log\LoggerInterface;
use Unirgy\DropshipPayout\Model\PayoutFactory;

class MassDelete extends AbstractPayout
{
    public function execute()
    {
        $payoutIds = $this->getRequest()->getParam('payout');
        if (!is_array($payoutIds)) {
            $this->messageManager->addError(__('Please select payout(s)'));
        }
        else {
            try {
                $updatedCnt = 0;
                foreach ($payoutIds as $payoutId) {
                    if (($payout = $this->_payoutFactory->create()->load($payoutId)) && $payout->getId()) {
                        $payout->delete();
                        $updatedCnt++;
                    }
                }
                $this->messageManager->addSuccess(
                    __('%1 of %2 record(s) were successfully deleted', $updatedCnt, count($payoutIds))
                );
            } catch (\Exception $e) {
                $this->_hlp->logError($e);
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
}
