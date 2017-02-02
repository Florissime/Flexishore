<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ControllerActionPredispatchAjaxcartCartAdd extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        if (($allowOrigin = $this->_getAccessAllowOrigin())) {
            header('Access-Control-Allow-Headers: X-Prototype-Version, X-Requested-With');
            header('Access-Control-Allow-Origin: '.$allowOrigin);
        }
    }
}
