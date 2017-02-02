<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CatalogProductCollectionLoadBefore extends AbstractObserver implements ObserverInterface
{
    /**
    * Filter products collections in adminhtml by current vendor
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        if (!($vendor = $this->_getVendor())) {
            return;
        }
        $collection = $observer->getEvent()->getCollection();
        $collection->addAttributeToFilter('udropship_vendor', $vendor->getId());
    }
}
