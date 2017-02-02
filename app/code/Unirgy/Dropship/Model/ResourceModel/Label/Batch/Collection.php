<?php

namespace Unirgy\Dropship\Model\ResourceModel\Label\Batch;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Unirgy\Dropship\Model\Label\Batch', 'Unirgy\Dropship\Model\ResourceModel\Label\Batch');
        parent::_construct();
    }
}