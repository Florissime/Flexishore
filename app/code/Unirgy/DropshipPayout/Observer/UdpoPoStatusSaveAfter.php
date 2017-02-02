<?php

namespace Unirgy\DropshipPayout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdpoPoStatusSaveAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $this->_udpo_po_save_after($observer);
    }
}
