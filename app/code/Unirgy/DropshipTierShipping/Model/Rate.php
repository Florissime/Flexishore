<?php

namespace Unirgy\DropshipTierShipping\Model;

use Magento\Framework\Model\AbstractModel;

class Rate extends AbstractModel
{
    protected function _construct()
    {
        $this->_setResourceModel('Unirgy\DropshipTierShipping\Model\ResourceModel\Rate');
        $this->getResource()->useRateSetup(
            $this->getData('__use_rate_setup'),
            $this->getData('__use_vendor'),
            $this->getData('__use_product')
        );
        $this->_idFieldName = $this->_getResource()->getIdFieldName();
    }

}