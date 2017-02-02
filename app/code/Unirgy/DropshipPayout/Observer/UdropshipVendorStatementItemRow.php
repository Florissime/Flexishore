<?php

namespace Unirgy\DropshipPayout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipPayout\Model\Payout;

class UdropshipVendorStatementItemRow extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $statementId = $observer->getEvent()->getStatement()->getStatementId();
        $sId = $observer->getEvent()->getPo()->getId();
        $eData = $observer->getEvent()->getData();
        $order = &$eData['order'];
        if (isset($this->_payoutHlp->statementPayoutsByPo[$statementId][$sId])) {
            $order['paid'] = $this->_payoutHlp->statementPayoutsByPo[$statementId][$sId]->getPayoutStatus()==Payout::STATUS_PAID
                || $this->_payoutHlp->statementPayoutsByPo[$statementId][$sId]->getPayoutStatus()==Payout::STATUS_PAYPAL_IPN;
        }
    }
}
