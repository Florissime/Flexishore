<?php

namespace Unirgy\DropshipPayout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdropshipVendorStatementPos extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $statement   = $observer->getEvent()->getStatement();
        $pos   = $observer->getEvent()->getPos();
        $statementId = $statement->getStatementId();
        /** @var \Unirgy\DropshipPayout\Model\ResourceModel\Payout\Collection $payoutCol */
        $payoutCol = $this->_hlp->createObj('\Unirgy\DropshipPayout\Model\ResourceModel\Payout\Collection');
        $this->_payoutHlp->statementPayouts[$statementId] = $payoutCol->loadStatementPayouts($statement, $pos);
        foreach ($this->_payoutHlp->statementPayouts[$statementId] as $sp) {
            foreach ($sp->initTotals()->getOrders() as $sId=>$order) {
                $_sId = explode('-', $sId);
                $this->_payoutHlp->statementPayoutsByPo[$statementId][$sId] = $sp;
                $this->_payoutHlp->statementPayoutsByPo[$statementId][$_sId[0]] = $sp;
            }
        }
    }
}
