<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class AdminhtmlCatalogProductEditElementTypes extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $observer->getResponse()->setTypes(array_merge($observer->getResponse()->getTypes(),
            array('udropship_vendor'=>'\Unirgy\Dropship\Block\Vendor\Htmlselect')
        ));
    }
}
