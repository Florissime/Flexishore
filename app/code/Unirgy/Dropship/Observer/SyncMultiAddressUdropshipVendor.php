<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

class SyncMultiAddressUdropshipVendor extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        if ($observer->getQuote()->getIsMultiShipping()) {
            foreach ($observer->getQuote()->getAllAddresses() as $address) {
                $address->getAllItems();
                $addressItems = $address->getItemsCollection();
                foreach ($addressItems as $addressItem) {
                    if ($addressItem->getQuoteItem()) {
                        $addressItem->setUdropshipVendor($addressItem->getQuoteItem()->getUdropshipVendor());
                    }
                }
            }
        }
    }
}
