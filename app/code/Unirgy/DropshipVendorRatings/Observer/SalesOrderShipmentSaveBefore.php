<?php

namespace Unirgy\DropshipVendorRatings\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SalesOrderShipmentSaveBefore extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $this->_sales_order_shipment_save_before($observer, false);
    }
}
