<?php

namespace Unirgy\DropshipTierShipping\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class DeliveryType
 * @package Unirgy\DropshipTierShipping\Model\ResourceModel
 */
class DeliveryType extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'udtiership_delivery_type_resource';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('udtiership_delivery_type', 'delivery_type_id');
    }
}
