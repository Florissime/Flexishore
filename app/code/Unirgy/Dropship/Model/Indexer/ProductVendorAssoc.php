<?php

namespace Unirgy\Dropship\Model\Indexer;

use Unirgy\Dropship\Model\Indexer\ProductVendorAssoc\Action\Full;
use Unirgy\Dropship\Model\Indexer\ProductVendorAssoc\Action\Row;
use Unirgy\Dropship\Model\Indexer\ProductVendorAssoc\Action\Rows;

class ProductVendorAssoc implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Eav\Action\Row
     */
    protected $_indexerRow;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Eav\Action\Rows
     */
    protected $_indexerRows;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Eav\Action\Full
     */
    protected $_indexerFull;

    /**
     * @param Row $indexerRow
     * @param Rows $indexerRows
     * @param Full $indexerFull
     */
    public function __construct(
        Row $indexerRow,
        Rows $indexerRows,
        Full $indexerFull
    ) {
        $this->_indexerRow = $indexerRow;
        $this->_indexerRows = $indexerRows;
        $this->_indexerFull = $indexerFull;
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @return void
     */
    public function execute($ids)
    {
        $this->_indexerRows->execute($ids);
    }

    /**
     * Execute full indexation
     *
     * @return void
     */
    public function executeFull()
    {
        $this->_indexerFull->execute();
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     * @return void
     */
    public function executeList(array $ids)
    {
        $this->_indexerRows->execute($ids);
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     * @return void
     */
    public function executeRow($id)
    {
        $this->_indexerRow->execute($id);
    }
}