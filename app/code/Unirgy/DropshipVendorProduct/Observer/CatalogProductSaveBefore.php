<?php

namespace Unirgy\DropshipVendorProduct\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CatalogProductSaveBefore extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        foreach (['udprod_attributes_changed','udprod_cfg_simples_added','udprod_cfg_simples_removed'] as $sAttr) {
            if (!$observer->getProduct()->hasData($sAttr)) {
                $observer->getProduct()->setData($sAttr, '');
            }
        }
    }
}
