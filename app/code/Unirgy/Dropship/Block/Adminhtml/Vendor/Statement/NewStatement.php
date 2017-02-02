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
 * @package    \Unirgy\Dropship
 * @copyright  Copyright (c) 2015-2016 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Statement;

use \Magento\Backend\Block\Widget\Form\Container;

class NewStatement extends Container
{
    public function _construct()
    {
        parent::_construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'Unirgy_Dropship';
        $this->_mode = 'NewStatement';
        $this->_controller = 'adminhtml_vendor_statement';

        $this->updateButton('save', 'label', __('Generate Statements'));
        $this->removeButton('delete');
        $this->setData('form_action_url', $this->getUrl('udropship/vendor_statement/newPost'));
    }

    public function getHeaderText()
    {
        return __('Generate Vendor Statements');
    }

}
