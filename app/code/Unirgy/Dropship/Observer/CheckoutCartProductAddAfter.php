<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class CheckoutCartProductAddAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        if (!$this->_hlp->isActive()) {
           return;
        }
        try {
            if ($observer->getQuoteItem() && ($quote = $observer->getQuoteItem()->getQuote())) {
                $quote->setRefreshVendorsFlag(true);
            }
        } catch (\Exception $e) {
            // all necessary actions should be already done by now, kill the exception
        }
        $this->setIsCartUpdateActionFlag(false);
    }
}
