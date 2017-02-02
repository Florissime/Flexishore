<?php

namespace Unirgy\DropshipShippingClass\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipShippingClass\Helper\Data as HelperData;

class UdropshipShippingLoadAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $this->_helperData->processShipClass($observer->getShipping(), 'vendor_ship_class');
        $this->_helperData->processShipClass($observer->getShipping(), 'customer_ship_class');
    }
}
