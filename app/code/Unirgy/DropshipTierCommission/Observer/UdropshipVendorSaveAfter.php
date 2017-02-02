<?php

namespace Unirgy\DropshipTierCommission\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipTierCommission\Helper\Data as HelperData;

class UdropshipVendorSaveAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $this->_hlp->processTiercomRates($observer->getVendor());
        $this->_hlp->processTiercomFixedRates($observer->getVendor());
    }
}
