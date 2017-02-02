<?php

namespace Unirgy\DropshipShippingClass\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

class Customer extends Container
{
    public function _construct()
    {
        $this->_blockGroup = 'Unirgy_DropshipShippingClass';
        $this->_controller = 'adminhtml_customer';
        $this->_headerText = __('Customer Ship Classes');
        parent::_construct();
    }
}
