<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CheckoutCartAddProductComplete extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        return;
        if (($allowOrigin = $this->_getAccessAllowOrigin())) {
            Mage::app()->getResponse()->setHeader('Access-Control-Allow-Origin', $allowOrigin);
            Mage::app()->getResponse()->setHeader('Access-Control-Allow-Headers', 'X-Prototype-Version, X-Requested-With');
        }
    }
}
