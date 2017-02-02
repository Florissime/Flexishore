<?php

namespace Unirgy\DropshipShippingClass\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

class Vendor extends Container
{
    public function _construct()
    {
        $this->_blockGroup = 'Unirgy_DropshipShippingClass';
        $this->_controller = 'adminhtml_vendor';
        $this->_headerText = __('Vendor Ship Classes');
        parent::_construct();
    }
}
