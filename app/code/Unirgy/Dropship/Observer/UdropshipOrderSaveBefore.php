<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class UdropshipOrderSaveBefore extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $order = $observer->getOrder();
        $this->changeOrderStatusAfterPosGenarated($order);
    }
}
