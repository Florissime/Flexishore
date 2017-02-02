<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Layout;

class ControllerActionLayoutRenderBefore extends AbstractObserver implements ObserverInterface
{
    /**
    * Make frontend layout/block changes vendor specific
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        if (!($vendor = $this->_getVendor())) {
            return;
        }
        $this->_hlp->getObj('\Magento\Framework\View\Page\Config')->addBodyClass('vendor-'.$vendor->getUrlKey());
    }
}
