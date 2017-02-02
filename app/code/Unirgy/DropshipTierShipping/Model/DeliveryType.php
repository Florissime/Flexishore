<?php

namespace Unirgy\DropshipTierShipping\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class DeliveryType
 * @package Unirgy\DropshipTierShipping\Model
 */
class DeliveryType extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'udtiership_delivery_type';

    /**
     * @var string
     */
    protected $_eventObject = 'delivery_type';

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Unirgy\DropshipTierShipping\Model\ResourceModel\DeliveryType');
    }
}
