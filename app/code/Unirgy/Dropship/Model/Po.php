<?php

namespace Unirgy\Dropship\Model;

use \Magento\Sales\Model\Order\Shipment;

class Po extends Shipment
{
    protected function _construct()
    {
        $this->_init('Unirgy\Dropship\Model\ResourceModel\Po');
    }
    public function unserializeFields()
    {
        $this->getResource()->unserializeFields($this);
        return $this;
    }
}