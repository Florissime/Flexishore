<?php

namespace Unirgy\DropshipVacation\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdropshipVendorSaveCommitAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $vendor = $observer->getVendor();
        $this->_vacHlp->processVendorChange($vendor);
    }
}
