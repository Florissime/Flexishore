<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class CatalogProductCollectionLoadBefore extends AbstractObserver implements ObserverInterface
{
    /**
    * Update stock status for product collection if augmented stock status is used
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        $observer->getEvent()->getCollection()->addAttributeToSelect('udropship_vendor');
    }
}
