<?php

namespace Unirgy\DropshipShippingClass\Model;

use Magento\Framework\Model\AbstractModel;

class Customer extends AbstractModel
{
    public function _construct()
    {
        $this->_init('Unirgy\DropshipShippingClass\Model\ResourceModel\Customer');
    }
}
