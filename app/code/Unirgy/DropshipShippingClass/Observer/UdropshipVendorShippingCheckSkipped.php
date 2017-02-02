<?php

namespace Unirgy\DropshipShippingClass\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdropshipVendorShippingCheckSkipped extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $shipping = $observer->getShipping();
        $vendor = $observer->getVendor();
        $address = $observer->getAddress();
        $result = $observer->getResult();
        $scHlp = $this->_helperData;
        $scHlp->processShipClass($shipping, 'vendor_ship_class');
        $scHlp->processShipClass($shipping, 'customer_ship_class');
        $_vClass = $scHlp->getAllVendorShipClass($vendor);
        $_cClass = $scHlp->getAllCustomerShipClass($address);
        $vClass = $shipping->getVendorShipClass();
        $cClass = $shipping->getCustomerShipClass();
        $resFlag = null;
        if (!empty($vClass) && is_array($vClass) && !array_intersect($_vClass, $vClass)) {
            $resFlag = true;
        }
        if (!empty($cClass) && is_array($cClass) && !array_intersect($_cClass, $cClass)) {
            $resFlag = true;
        }
        if ($resFlag !== null) {
            $result->setResult($resFlag);
        }
    }
}
