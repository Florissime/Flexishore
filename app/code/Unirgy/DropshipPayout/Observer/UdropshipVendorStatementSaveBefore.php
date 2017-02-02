<?php

namespace Unirgy\DropshipPayout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipPayout\Model\Payout;

class UdropshipVendorStatementSaveBefore extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $statement = $observer->getEvent()->getStatement();
        $statementId = $statement->getStatementId();
        if (!$statement->getId() && isset($this->_payoutHlp->statementPayouts[$statementId])) {
            foreach ($this->_payoutHlp->statementPayouts[$statementId] as $pt) {
                if ($pt->getPayoutStatus() != Payout::STATUS_CANCELED
                    && $pt->getPayoutStatus() != Payout::STATUS_PAID
                    && $pt->getPayoutStatus() != Payout::STATUS_PAYPAL_IPN
                ) {
                    $pt->setPayoutStatus(Payout::STATUS_HOLD);
                }
                $pt->setStatementId($statementId)->save();
            }
            $statement->getResource()->markPosHold($statement);
        }
    }
}
