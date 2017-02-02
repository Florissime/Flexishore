<?php

namespace Unirgy\Dropship\Model\Indexer\VendorProductAssoc\Action;

class Row extends \Unirgy\Dropship\Model\Indexer\AbstractAction
{
    public function execute($id=null)
    {
        if (!isset($id) || empty($id)) {
            throw new \Magento\Framework\Exception\InputException(
                __('We can\'t rebuild the index for an undefined product.')
            );
        }
        try {
            $this->_resource->reindexVendors([$id]);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()), $e);
        }
    }
}
