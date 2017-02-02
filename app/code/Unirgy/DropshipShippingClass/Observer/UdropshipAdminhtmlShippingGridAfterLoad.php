<?php

namespace Unirgy\DropshipShippingClass\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdropshipAdminhtmlShippingGridAfterLoad extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $grid = $observer->getGrid();
        foreach ($grid->getCollection() as $shipping) {
            $this->_helperData->processShipClass($shipping, 'vendor_ship_class');
            $this->_helperData->processShipClass($shipping, 'customer_ship_class');
        }
    }
}
