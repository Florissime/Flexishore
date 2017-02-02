<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipMicrosite
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipMicrosite\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

class Registration extends Container
{
    public function _construct()
    {
        $this->_blockGroup = 'Unirgy_DropshipMicrosite';
        $this->_controller = 'adminhtml_registration';
        $this->_headerText = __('Manage Registrations');
        parent::_construct();
        $this->removeButton('add');
    }
}