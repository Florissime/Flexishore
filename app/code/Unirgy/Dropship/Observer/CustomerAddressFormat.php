<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class CustomerAddressFormat extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $address = $observer->getAddress();
        $type = $observer->getType();
        if (!$type->getData('__udorig_default_format')) {
            $type->setData('__udorig_default_format', $type->getDefaultFormat());
        }
        $type->setDefaultFormat($type->getData('__udorig_default_format'));
        $vendor = $address->getData('__udropship_vendor');
        if ($vendor) {
            $flagKey = 'custom_'.$type->getCode().'_address_format';
            $customKey = $type->getCode().'_address_format';
            $store = $this->_hlp->getObj('\Magento\Customer\Model\Address\Config')->getStore();
            if ($vendor->getData($flagKey)) {
                if (-1 !== (int)$vendor->getData($flagKey)
                    && ($format = $vendor->getData($customKey))
                ) {
                    $type->setDefaultFormat($format);
                } elseif ($this->_hlp->getScopeFlag('udropship/customer/'.$flagKey, $store)
                    && ($format = $this->_hlp->getScopeConfig('udropship/customer/'.$customKey, $store))
                ) {
                    $type->setDefaultFormat($format);
                }
            }
        }
    }
}
