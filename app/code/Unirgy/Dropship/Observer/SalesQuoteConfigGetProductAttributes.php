<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class SalesQuoteConfigGetProductAttributes extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $paths = $this->getRuntimeProductAttributesConfigPaths();
        foreach ($paths as $path) {
            $path = str_replace('-', '/', $path);
            if (($attrCode = $this->scopeConfig->getValue($path))
                && $this->_hlp->checkProductCollectionAttribute($attrCode)
            ) {
                $observer->getAttributes()->setData($attrCode, $attrCode);
            }
        }
    }
}
