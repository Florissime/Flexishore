<?php

namespace Unirgy\DropshipTierShipping\Model\ResourceModel\DeliveryType;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_eventPrefix = 'udtiership_delivery_type_collection';
    protected $_eventObject = 'delivery_type_collection';

    protected function _construct()
    {
        $this->_init('Unirgy\DropshipTierShipping\Model\DeliveryType', 'Unirgy\DropshipTierShipping\Model\ResourceModel\DeliveryType');
    }

    public function setDeliverySort($dir='ASC')
    {
        $this->setOrder('main_table.sort_order', $dir);
        return $this;
    }

    public function toOptionHash()
    {
        return $this->_toOptionHash('delivery_type_id', 'delivery_title');
    }
    public function toOptionArray()
    {
        return $this->_toOptionArray('delivery_type_id', 'delivery_title');
    }
}