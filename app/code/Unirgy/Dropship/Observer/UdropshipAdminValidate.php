<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Helper\ProtectedCode;
use \Unirgy\Dropship\Observer\AbstractObserver;

class UdropshipAdminValidate extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        try {
            ProtectedCode::validateLicense($observer->getModule());
        } catch (\Exception $e) {
            $this->_hlp->session()->addError($e->getMessage());
        }
    }
}
