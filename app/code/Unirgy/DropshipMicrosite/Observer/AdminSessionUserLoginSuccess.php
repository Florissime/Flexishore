<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AdminSessionUserLoginSuccess extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $oId = $this->_hlp->session()->getSessionId();
        $sId = !empty($_COOKIE['frontend']) ? $_COOKIE['frontend'] : null;

        $user = $observer->getEvent()->getUser();
        $vendorId = $user->getUdropshipVendor();

        if ($user->getUdropshipVendor()) {
            $this->_switchSession(\Magento\Framework\App\Area::AREA_FRONTEND, $sId);
            $session = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
            if (!$session->isLoggedIn()) {
                $session->loginById($vendorId);
            }
            $this->_switchSession(\Magento\Framework\App\Area::AREA_ADMINHTML, $oId, true);
        }
    }
}
