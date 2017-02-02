<?php

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

class MassPay extends AbstractPayout
{
    public function execute()
    {
        $modelIds = (array)$this->getRequest()->getParam('payout');
        $status     = (string)$this->getRequest()->getParam('status');

        try {
            $ptCollection = $this->_payoutFactory->create()->getCollection()->addFieldToFilter('payout_id', $modelIds);
            $ptCollection->pay();
            $paidCnt = 0;
            foreach ($ptCollection as $pt) {
                if ($pt->getIsJustPaid()) $paidCnt += 1;
            }
            $this->messageManager->addSuccess(
                __('%1 of %2 payouts  were successfully paid', $paidCnt, count($modelIds))
            );
        }
        catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_hlp->logError($e);
            $this->messageManager->addError($e->getMessage());
        }
        catch (\Exception $e) {
            $this->_hlp->logError($e);
            $this->messageManager->addException($e, __('There was an error during payout(s) mass pay'));
        }

        $this->_redirect('*/*/');
    }
}
