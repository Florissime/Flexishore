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

namespace Unirgy\Dropship\Block\Adminhtml;

use \Magento\Backend\Block\Widget\Grid\Container;

class Batch extends Container
{

    protected function _construct()
    {
        parent::_construct();
        $this->_blockGroup = 'Unirgy_Dropship';
        $this->_controller = 'adminhtml_batch';
        $this->_headerText = __('Label Batches');

        $this->removeButton('add');
    }

}
