<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class CatalogControllerProductInit extends AbstractObserver implements ObserverInterface
{
    /**
    * Deny access to product if not from current vendor and accessed directly by URL
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        if (!($vendor = $this->_getVendor())
            || $this->_hlp->isCurrentVendorFromProduct()
        ) {
            return;
        }
        $product = $observer->getEvent()->getProduct();
        $isMyProduct = $product->getUdropshipVendor()==$vendor->getId();
        $showAll = $this->scopeConfig->isSetFlag('udropship/microsite/front_show_all_products', ScopeInterface::SCOPE_STORE);
        $isUdmulti = $this->_hlp->isUdmultiActive();
        $isInUdm = $product->getUdmultiStock($vendor->getId());
        if (!$isMyProduct && !($showAll && $isUdmulti && $isInUdm)) {
            //throw new \Exception('Product is filtered out by vendor');
        }
    }
}
