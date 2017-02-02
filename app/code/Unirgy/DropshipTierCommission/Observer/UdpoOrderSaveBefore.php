<?php

namespace Unirgy\DropshipTierCommission\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdpoOrderSaveBefore extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $order = $observer->getOrder();
        $pos = $observer->getUdpos();

        foreach ($pos as $po) {
            $this->_hlp->processPo($po);
        }
    }
}
