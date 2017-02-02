<?php

namespace Unirgy\Dropship\Model\ResourceModel\Label;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Batch extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('udropship_label_batch', 'batch_id');
    }
}