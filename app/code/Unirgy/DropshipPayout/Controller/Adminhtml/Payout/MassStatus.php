<?php

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

use Unirgy\DropshipPayout\Model\Payout;

class MassStatus extends AbstractPayout
{
    public function execute()
    {
        $payoutIds = (array)$this->getRequest()->getParam('payout');
        $status     = (string)$this->getRequest()->getParam('status');

        try {
            $updatedCnt = 0;
            foreach ($payoutIds as $payoutId) {
                if (($payout = $this->_payoutFactory->create()->load($payoutId)) && $payout->getId()) {
                    if ($status == Payout::STATUS_CANCELED) {
                        $payout->cancel();
                    } else {
                        $payout->setPayoutStatus($status)->save();
                    }
                    $updatedCnt++;
                }
            }
            $this->messageManager->addSuccess(
                __('%1 of %d record(s) were successfully updated', $updatedCnt, count($payoutIds))
            );
        }
        catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        }
        catch (\Exception $e) {
            $this->messageManager->addException($e, __('There was an error while updating payout(s) status'));
        }

        $this->_redirect('*/*/');
    }
}
