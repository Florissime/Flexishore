<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class SalesConvertQuoteItemToOrderItem extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $orderItem = $observer->getEvent()->getOrderItem();
        $quoteItem = $observer->getEvent()->getItem();
        $weightType = $quoteItem->getProduct()->getWeightType();

        if (!$this->getParentItem() && $weightType!==null) {
            $productOptions = $orderItem->getProductOptions();
            $productOptions['weight_type'] = $weightType;
            $productOptions['udropship_vendor'] = $quoteItem->getUdropshipVendor();
            $orderItem->setProductOptions($productOptions);
        }
    }
}
