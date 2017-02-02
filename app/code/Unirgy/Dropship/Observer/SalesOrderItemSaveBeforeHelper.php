<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\DataObject;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class SalesOrderItemSaveBeforeHelper extends AbstractObserver implements ObserverInterface
{
    /**
    * Before 1.4.1.x
    *
    * @param DataObject $observer
    */
    public function execute(Observer $observer)
    {
        $this->setOrderItem($observer->getEvent()->getItem());
    }
}
