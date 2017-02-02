<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Catalog\Model\Product\Type;
use \Magento\Catalog\Model\Product\Type\AbstractType;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Helper\Item;
use \Unirgy\Dropship\Observer\AbstractObserver;
use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class SalesQuoteItemSetProduct extends AbstractObserver implements ObserverInterface
{
    /**
     * @var Item
     */
    protected $_iHlp;

    public function __construct(
        Item $helperItem,
        \Unirgy\Dropship\Observer\Context $context,
        array $data = []
    )
    {
        $this->_iHlp = $helperItem;
        parent::__construct($context, $data);
    }

    public function execute(Observer $observer)
    {
        $iHlp = $this->_iHlp;
        $item = $observer->getEvent()->getQuoteItem();
        $product = $observer->getEvent()->getProduct();
        $sId = $item->getStoreId();

        $specialCost = $this->_hlp->getScopeConfig('udropship/vendor/special_cost_attribute');
        if ($specialCost && ($specialCost = $product->getData($specialCost))) {
            $baseCost = $item->getBaseCost();
            $specialFrom = $product->getSpecialFromDate();
            $specialTo = $product->getSpecialToDate();
            if ($this->_hlp->isScopeDateInInterval($sId, $specialFrom, $specialTo)) {
                $item->setBaseCost(min($baseCost, $specialCost));
            };
        }

        if (($parent = $item->getParentItem()) && !$item->getBaseCost()) {
            if ($parent->getProductType() == Configurable::TYPE_CODE) {
                $item->setBaseCost($parent->getPrice());
            } else {
                $item->setBaseCost($product->getPrice());
            }
        }

        if (($parent = $item->getParentItem()) && $parent->getProductType() == Configurable::TYPE_CODE) {
            $iHlp->setUdropshipVendor($parent, $item->getUdropshipVendor());
            $parent->setBaseCost($item->getBaseCost());
        }

        $shipmentType = $product->getShipmentType();
        $shipmentTypeFlag = (null !== $shipmentType)
            && (int)$shipmentType===AbstractType::SHIPMENT_SEPARATELY;
        $priceType = $product->getPriceType();
        $priceTypeFlag = (null !== $priceType)
            && (int)$priceType===AbstractType::CALCULATE_CHILD;
        $weightType = $product->getWeightType();
        $weightTypeFlag = (null !== $weightType) && !$weightType;

        if (!$this->_hlp->getScopeFlag('udropship/stock/skip_bundle_limit')
            && $shipmentTypeFlag && (!$priceTypeFlag || !$weightTypeFlag)
        ) {
            $product->setShipmentType(
                AbstractType::SHIPMENT_TOGETHER
            );
        }

    }
}
