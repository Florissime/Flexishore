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
 
namespace Unirgy\Dropship\Model\Vendor\Statement;

use \Magento\Framework\Model\AbstractModel;

class Row extends AbstractModel
{
    protected $_eventPrefix = 'vendor_statement_row';
    protected $_eventObject = 'statement_row';

    protected function _construct()
    {
        $this->_init('Unirgy\Dropship\Model\ResourceModel\Vendor\Statement\Row');
    }
}
