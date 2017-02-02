<?php

namespace Unirgy\DropshipTierCommission\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdropshipVendorSaveBefore extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $this->_hlp->processTiercomRates($observer->getVendor(), true);
        $this->_hlp->processTiercomFixedRates($observer->getVendor(), true);
    }
}
