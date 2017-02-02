<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class AdminhtmlControllerActionPredispatchStart extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $this->_hlp->resetCurrentVendor();
        $this->_hlp->checkPermission('adminhtml');
        if ($this->_getVendor()) {
            $adminTheme = $this->scopeConfig->getValue('udropship/admin/interface_theme', ScopeInterface::SCOPE_STORE, 0);
            if (!empty($adminTheme)) {
                $this->_hlp->setDesignStore(true, \Magento\Framework\App\Area::AREA_ADMINHTML, $adminTheme);
            }
        }
    }
}
