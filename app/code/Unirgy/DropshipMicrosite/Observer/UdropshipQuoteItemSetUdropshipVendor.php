<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class UdropshipQuoteItemSetUdropshipVendor extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $iHlp = $this->_iHlp;
        $item = $observer->getItem();
        $stickUdms = $this->scopeConfig->getValue('udropship/stock/stick_microsite', ScopeInterface::SCOPE_STORE);
        if ($stickUdms>0) {
            $iHlp->deleteVendorIdOption($item);
            if (in_array($stickUdms, [2])
                && $iHlp->getForcedVendorIdOption($item)==$item->getUdropshipVendor()
            ) {
                $iHlp->setVendorIdOption($item, $item->getUdropshipVendor());
            } elseif (in_array($stickUdms, [4])
                && $iHlp->getPriorityVendorIdOption($item)==$item->getUdropshipVendor()
            ) {
                $iHlp->setVendorIdOption($item, $item->getUdropshipVendor());
            }
        }
    }
}
