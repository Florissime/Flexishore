<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class AdminhtmlControllerActionPredispatch extends AbstractObserver implements ObserverInterface
{
    /**
    * Check for extension update news
    *
    * @param Observer $observer
    */
    public function execute(Observer $observer)
    {
        if ($this->_hlp->getScopeConfig('udropship/admin/notifications')) {
            try {
                $this->_hlp->createObj('\Unirgy\Dropship\Model\Feed')->checkUpdate();
            } catch (\Exception $e) {
                // silently ignore
            }
        }
    }
}
