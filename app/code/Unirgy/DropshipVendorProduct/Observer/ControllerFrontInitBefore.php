<?php

namespace Unirgy\DropshipVendorProduct\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ControllerFrontInitBefore extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $this->_initConfigRewrites();
    }
}
