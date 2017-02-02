<?php

namespace Unirgy\DropshipShippingClass\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdropshipShippingSaveBefore extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $r = $this->_appRequestInterface;
        if ($r->getParam('vendor_ship_class')) {
            $observer->getShipping()->setData('vendor_ship_class', $r->getParam('vendor_ship_class'));
        }
        if ($r->getParam('customer_ship_class')) {
            $observer->getShipping()->setData('customer_ship_class', $r->getParam('customer_ship_class'));
        }
        $this->_helperData->processShipClass($observer->getShipping(), 'vendor_ship_class', true);
        $this->_helperData->processShipClass($observer->getShipping(), 'customer_ship_class', true);
    }
}
