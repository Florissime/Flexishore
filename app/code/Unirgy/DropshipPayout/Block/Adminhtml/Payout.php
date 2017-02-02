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
 * @package    Unirgy_DropshipPayout
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipPayout\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

class Payout extends Container
{

    public function _construct()
    {
        $this->_blockGroup = 'Unirgy_DropshipPayout';
        $this->_controller = 'adminhtml_payout';
        $this->_headerText = __('Vendor Payouts');
        $this->_addButtonLabel = __('Generate Payouts');
        parent::_construct();
    }

}
