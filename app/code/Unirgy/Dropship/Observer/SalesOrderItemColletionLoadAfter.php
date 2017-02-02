<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\DataObject;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class SalesOrderItemColletionLoadAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $items = $observer->getEvent()->getOrderItemCollection();
        foreach ($items as $item) {
            $prodOptions = $item->getProductOptions();
            $checkTimes = 0;
            while (!is_array($prodOptions) && ++$checkTimes<10) {
                $prodOptions = unserialize($prodOptions);
            }
            $item->setProductOptions($prodOptions);
        }
    }
}
