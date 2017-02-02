<?php

namespace Unirgy\DropshipVendorRatings\Block\Adminhtml\Rating;

use Magento\Backend\Block\Widget\Grid\Container;

class Rating extends Container
{
    protected $_blockGroup = 'Unirgy_DropshipVendorRatings';
    protected function _construct()
    {
        $this->_controller = 'adminhtml_rating';
        $this->_headerText = __('Manage Ratings');
        $this->_addButtonLabel = __('Add New Rating');
        parent::_construct();
    }
}
