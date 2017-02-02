<?php

namespace Unirgy\DropshipPayout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipPayout\Model\Payout;

class UdropshipVendorStatementCollectPayouts extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $statement   = $observer->getEvent()->getStatement();
        $statementId = $statement->getStatementId();
        $totalPaid = 0;
        $paymentPaid = 0;
        foreach ($this->_payoutHlp->statementPayouts[$statementId] as $sp) {
            $statement->addPayout($sp);
            if ($sp->getPayoutStatus() == Payout::STATUS_PAID
                || $sp->getPayoutStatus() == Payout::STATUS_PAYPAL_IPN
            ) {
                $totalPaid += $sp->getTotalPaid();
                $paymentPaid += $sp->getPaymentPaid();
                foreach ($sp->getAdjustments($this->_hlp->getAdjustmentPrefix('payout')) as $adj) {
                    $statement->addAdjustment($adj);
                }
            }
        }
        $statement->setPaymentPaid($statement->getPaymentPaid()+$paymentPaid);
        $statement->setTotalPaid($statement->getTotalPaid()+$totalPaid);
    }
}
