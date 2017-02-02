<?php

namespace Unirgy\DropshipTierShipping\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

class UdropshipVendorSaveAfter extends AbstractObserver implements ObserverInterface
{

    public function __construct(HelperData $helperData, 
        StoreManagerInterface $modelStoreManagerInterface)
    {


        parent::__construct($helperData, $modelStoreManagerInterface);
    }

    public function execute(Observer $observer)
    {
        $v = $observer->getVendor();
        $this->_tsHlp->processTiershipRates($v);
        $this->_tsHlp->processTiershipSimpleRates($v);
        $this->_tsHlp->saveVendorV2Rates($v->getId(), $v->getData('tiership_v2_rates'));
        $this->_tsHlp->saveVendorV2SimpleRates($v->getId(), $v->getData('tiership_v2_simple_rates'));
        $this->_tsHlp->saveVendorV2SimpleCondRates($v->getId(), $v->getData('tiership_v2_simple_cond_rates'));
    }
}
