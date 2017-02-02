<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class BeforeSubmitOrder extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $observer->getEvent()->getOrder()->setNoDropshipFlag(true);
        $observer->getEvent()->getOrder()->setData('ud_amount_fields', 1);
        $observer->getEvent()->getOrder()->setData('udpo_amount_fields', 1);
        $this->unsQuote();
    }
}
