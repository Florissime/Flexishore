<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Model\Stock\Availability;
use \Unirgy\Dropship\Observer\AbstractObserver;

class CatalogProductCollectionLoadAfter extends AbstractObserver implements ObserverInterface
{
    /**
     * @var Availability
     */
    protected $_stockAvailability;
    protected $_stockRegistry;

    public function __construct(
        Availability $stockAvailability,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Unirgy\Dropship\Observer\Context $context,
        array $data = []
    )
    {
        $this->_stockRegistry = $stockRegistry;
        $this->_stockAvailability = $stockAvailability;

        parent::__construct($context, $data);
    }

    public function execute(Observer $observer)
    {
        $productCollection = $observer->getEvent()->getCollection();

        $hlp = $this->_hlp;
        $storeId = null;
        foreach ($productCollection as $product) {
            if (is_null($storeId)) {
                $storeId = $product->getStoreId();
                $localVendorId = $hlp->getLocalVendorId($storeId);
            }
            $vendorId = $hlp->getProductVendorId($product);
            if ($vendorId==$localVendorId) {
                continue;
            }
            if ($this->_stockAvailability->getUseLocalStockIfAvailable($storeId, $vendorId)) {
                $product->setIsSalable(true);
                $this->_stockRegistry->getStockItem($product->getId())->setIsInStock(true);
            }
        }
    }
}
