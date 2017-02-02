<?php
namespace Unirgy\Dropship\Plugin;

use Magento\Catalog\Model\Product\Attribute\Repository;

class CatalogProductAction
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
    public function afterUpdateAttributes(\Magento\Catalog\Model\Product\Action $productAction, $result)
    {
        $indexerRegistry = $this->indexerRegistry;
        $indexerConfig = $this->indexerConfig;
        $indexerId = \Unirgy\Dropship\Model\Indexer\ProductVendorAssoc\Processor::INDEXER_ID;
        if ($indexerConfig->getIndexer($indexerId)) {
            $indexer = $indexerRegistry->get($indexerId);
            if ($indexer && !$indexer->isScheduled()) {
                $indexer->reindexList($productAction->getProductIds());
            }
        }
        return $result;
    }
}
