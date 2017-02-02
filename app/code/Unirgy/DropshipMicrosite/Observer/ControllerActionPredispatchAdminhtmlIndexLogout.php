<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ControllerActionPredispatchAdminhtmlIndexLogout extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $user = ObjectManager::getInstance()->get('Magento\Backend\Model\Session')->getUser();
        if ($user) {
            $this->_vendorId = $user->getUdropshipVendor();
        }
    }
}
