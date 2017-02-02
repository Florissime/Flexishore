<?php

namespace Unirgy\DropshipTierShipping\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdprodProductEditElementTypes extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $response = $observer->getResponse();
        $types = $response->getTypes();
        $types['udtiership_rates'] = '\Unirgy\DropshipTierShipping\Block\Vendor\Product\Form\Rates';
        $types['text_udtiership_rates'] = '\Unirgy\DropshipTierShipping\Block\Vendor\Product\Form\Rates';
        $response->setTypes($types);
    }
}
