<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CoreBlockAbstractPrepareLayoutBefore extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $this->_limitStoreSwitcher($observer->getBlock());
    }
}
