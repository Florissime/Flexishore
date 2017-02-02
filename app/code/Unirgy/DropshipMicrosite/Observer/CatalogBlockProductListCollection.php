<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CatalogBlockProductListCollection extends AbstractObserver implements ObserverInterface
{
    /**
    * Filter products collections on frontend by current vendor
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        $this->_msHlp->addVendorFilterToProductCollection($observer->getEvent()->getCollection());
    }
}
