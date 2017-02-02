<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class MultishippingBeforeSubmitOrder extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $observer->getEvent()->getOrder()->setNoDropshipFlag(true);
    }
}
