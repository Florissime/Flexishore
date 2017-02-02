<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CatalogProductTypePrepareCartOptions extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $this->_catalog_product_type_prepare_cart_options($observer);
    }
}
