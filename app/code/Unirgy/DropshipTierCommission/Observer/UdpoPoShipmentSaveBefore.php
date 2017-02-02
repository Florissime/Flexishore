<?php

namespace Unirgy\DropshipTierCommission\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipTierCommission\Helper\Data as HelperData;

class UdpoPoShipmentSaveBefore extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $order = $observer->getOrder();
        $pos = $observer->getShipments();

        foreach ($pos as $po) {
            $this->_hlp->processPo($po);
        }
    }
}
