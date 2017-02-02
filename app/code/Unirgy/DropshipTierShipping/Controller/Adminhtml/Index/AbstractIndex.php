<?php

namespace Unirgy\DropshipTierShipping\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;

abstract class AbstractIndex extends Action
{

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Unirgy_Dropship::udropship');
    }
}