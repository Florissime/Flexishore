<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class SalesModelServiceQuoteSubmitBeforeHelper extends AbstractObserver implements ObserverInterface
{
    /**
    * After 1.4.1.x
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        $this->setQuote($observer->getEvent()->getQuote());
    }
}
