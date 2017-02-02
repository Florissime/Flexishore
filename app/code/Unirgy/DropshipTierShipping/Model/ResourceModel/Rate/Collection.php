<?php

namespace Unirgy\DropshipTierShipping\Model\ResourceModel\Rate;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package Unirgy\DropshipTierShipping\Model\ResourceModel\Rate
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('Unirgy\DropshipTierShipping\Model\Rate', 'Unirgy\DropshipTierShipping\Model\ResourceModel\Rate');
    }
}
