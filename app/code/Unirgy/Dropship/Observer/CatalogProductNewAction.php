<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\DataObject;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class CatalogProductNewAction extends AbstractObserver implements ObserverInterface
{
    /**
    * Set default local vendor for new products
    *
    * @param DataObject $observer
    */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $product->setUdropshipVendor($this->_hlp->getLocalVendorId($product->getStoreId()));
    }
}
