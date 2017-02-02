<?php

namespace Unirgy\Dropship\Model\Indexer\ProductVendorAssoc\Action;

class Rows extends \Unirgy\Dropship\Model\Indexer\AbstractAction
{
    public function execute($ids)
    {
        try {
            $this->_resource->reindexProducts($ids);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()), $e);
        }
    }
}
