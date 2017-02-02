<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;

class ControllerFrontInitBefore extends AbstractObserver implements ObserverInterface
{
    /**
    * Invoke as soon as possible to get correct base_url in frontend
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        if ($vendor = $this->_getVendor()) {
            $this->_hlp->setScopeConfig("web/default/front", 'umicrosite', true);
        }
        $this->_initConfigRewrites();

    }
}
