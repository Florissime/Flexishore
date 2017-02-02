<?php

namespace Unirgy\DropshipTierShipping\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

class UdropshipVendorLoadAfter extends AbstractObserver implements ObserverInterface
{

    public function __construct(HelperData $helperData, 
        StoreManagerInterface $modelStoreManagerInterface)
    {


        parent::__construct($helperData, $modelStoreManagerInterface);
    }

    public function execute(Observer $observer)
    {
        $this->_tsHlp->processTiershipRates($observer->getVendor());
        $this->_tsHlp->processTiershipSimpleRates($observer->getVendor());
    }
}
