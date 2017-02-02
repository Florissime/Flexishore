<?php

namespace Unirgy\DropshipShippingClass\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipShippingClass\Helper\Data as HelperData;

class UdropshipAdminhtmlVendorEditPrepareShippingGrid extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $vendor = $observer->getVendor();
        $collection = $observer->getCollection();
        $collection->addFieldToFilter('vendor_ship_class', [
            ['null'=>true],
            ['eq'=>''],
            ['finset'=>$this->_helperData->getVendorShipClass($vendor)],
        ]);
    }
}
