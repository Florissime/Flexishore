<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

class CatalogProductCommitAfter implements ObserverInterface
{
    protected $indexerRegistry;
    protected $indexerConfig;
    public function __construct(
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Indexer\Model\Config $indexerConfig
    )
    {
        $this->indexerRegistry = $indexerRegistry;
        $this->indexerConfig = $indexerConfig;
    }
    public function execute(Observer $observer)
    {
        $indexerRegistry = $this->indexerRegistry;
        $indexerConfig = $this->indexerConfig;
        $indexerId = \Unirgy\Dropship\Model\Indexer\ProductVendorAssoc\Processor::INDEXER_ID;
        if ($indexerConfig->getIndexer($indexerId)) {
            $indexer = $indexerRegistry->get($indexerId);
            if ($indexer && !$indexer->isScheduled()) {
                $indexer->reindexList([$observer->getProduct()->getId()]);
            }
        }
    }
}
