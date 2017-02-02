<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CatalogProductEditAction extends AbstractObserver implements ObserverInterface
{
    /**
    * Deny access to product editing in admin if does not belong to logged in vendor
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $vendor = $this->_getVendor();
        if ($product && $vendor && $product->getUdropshipVendor()!=$vendor->getId()) {
            throw new \Exception('Access denied');
        }
    }
}
