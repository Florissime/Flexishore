<?php

namespace Unirgy\Dropship\Model\Indexer\ProductVendorAssoc\Action;

class Full extends \Unirgy\Dropship\Model\Indexer\AbstractAction
{
    public function execute($ids = null)
    {
        try {
            $this->_resource->reindexAll();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()), $e);
        }
    }
}
