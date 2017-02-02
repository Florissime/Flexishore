<?php

namespace Unirgy\DropshipVendorProduct\Observer;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SalesQuoteConfigGetProductAttributes extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $attributes = $observer->getAttributes()->getData();
        $res = $this->_hlp->rHlp();
        $conn = $res->getConnection();
        $cfgAttrIds = $conn->fetchCol(
            $conn->select()->from($res->getTableName('catalog_product_super_attribute'), 'attribute_id')->distinct(true)
        );
        $cfgAttrs = $conn->fetchPairs(
            $conn->select()->from(['ea' => $res->getTableName('eav_attribute')], ['attribute_code', 'attribute_id'])
                ->where('attribute_id in (?)', $cfgAttrIds)
        );
        if (!empty($cfgAttrs)) {
            $observer->getAttributes()->addData($cfgAttrs);
        }
    }
}
