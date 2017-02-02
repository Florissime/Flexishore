<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Model\Source;
use \Unirgy\Dropship\Observer\AbstractObserver;

class SalesOrderShipmentLoadAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $shipment = $observer->getShipment();
        $status = $this->getUdropshipStatus();
        $statuses = $this->_hlp->src()->setPath('shipment_statuses')->toOptionHash();
        $statusName = isset($statuses[$status]) ? $statuses[$status] : (in_array($status, $statuses) ? $status : 'Unknown');
        $shipment->setUdropshipStatusName($statusName);
    }
}
