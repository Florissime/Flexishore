<?php

namespace Unirgy\DropshipVendorProduct\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;

class SalesQuoteLoadAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(EventObserver $observer)
    {
        return;
        $hl = $this->_hlp;
        $quote = $observer->getQuote();
        $qId = $quote->getId();
        if ($hl->isSkipQuoteLoadAfterEvent($qId) || $this->_modelObserver->getIsCartUpdateActionFlag()) return;
        $usedProducts = [];
        $cfgProducts = [];
        foreach ($quote->getAllItems() as $item) {
            if (($cpOpt = $item->getOptionByCode('cpid'))) {
                $cpId = $cpOpt->getValue();
                if (empty($usedProducts[$cpId])) {
                    $usedProducts[$cpId] = [];
                }
                $item->setName($cpOpt->getProduct()->getName());
                $cfgProducts[$cpId] = $cpOpt->getProduct();
                $usedProducts[$cpId][$item->getProduct()->getId()] = $item->getProduct();
            }
        }
        foreach ($usedProducts as $cpId => $ups) {
            if (!$cfgProducts[$cpId]->hasData('_cache_instance_products')) {
                $cfgProducts[$cpId]->setData('_cache_instance_products', $ups);
            }
        }
    }
}
