<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Catalog\Model\Product;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class UdropshipVendorSaveCommitAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $vendor = $observer->getVendor();
        $this->_hlp->createObj('\Unirgy\Dropship\Model\ProductImage')->clearCache($vendor);
        $pIds = $vendor->getData('__reindex_product_ids');
        if (!empty($pIds)) {

            /* @var \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry */
            $indexerRegistry = $this->_hlp->getObj('\Magento\Framework\Indexer\IndexerRegistry');
            /* @var \Magento\Indexer\Model\Config $indexerConfig */
            $indexerConfig = $this->_hlp->getObj('\Magento\Indexer\Model\Config');

            foreach ([
                \Unirgy\Dropship\Model\Indexer\ProductVendorAssoc\Processor::INDEXER_ID,
                \Magento\CatalogInventory\Model\Indexer\Stock\Processor::INDEXER_ID,
                \Magento\Catalog\Model\Indexer\Product\Price\Processor::INDEXER_ID,
                \Magento\Catalog\Model\Indexer\Product\Flat\Processor::INDEXER_ID,
                \Magento\Catalog\Model\Indexer\Product\Eav\Processor::INDEXER_ID,
                \Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID
            ] as $indexerId) {
                if (!$indexerConfig->getIndexer($indexerId)) continue;
                $indexer = $indexerRegistry->get($indexerId);
                if ($indexer && !$indexer->isScheduled()) {
                    $indexer->reindexList($pIds);
                }
            }

            if ($this->_hlp->isEE()) {
                //$this->_appCacheInterface->invalidateType('full_page');
            }
            if ($this->_hlp->isModuleActive('Nexcessnet_Turpentine')) {
                $result = $this->_hlp->createObj('\Nexcessnet\Turpentine\Model\Varnish\Admin')->flushAll();
                $this->_eventManager->dispatch( 'turpentine_ban_all_cache', $result );
            }
        }
        $vendor->unsetData('__reindex_product_ids');
    }
}
