<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Model\Source;
use \Unirgy\Dropship\Observer\AbstractObserver;

class SalesOrderItemCancel extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $item = $observer->getItem();
        $order = $item->getOrder();
        foreach ($order->getShipmentsCollection() as $shipment) {
            $canCancel = !in_array($shipment->getUdropshipStatus(), array(
                Source::SHIPMENT_STATUS_SHIPPED,
                Source::SHIPMENT_STATUS_DELIVERED,
                Source::SHIPMENT_STATUS_CANCELED,
            ));
            if ($canCancel) {
                $canCancel = false;
                foreach ($shipment->getAllItems() as $sItem) {
                    if ($sItem->getOrderItemId()==$item->getId() && $item->getQtyToCancel()) {
                        $canCancel = true;
                        break;
                    }
                }
                $shipment->setUdCanCancel($shipment->getUdCanCancel()||$canCancel);
            }
        }
    }
}
