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

namespace Unirgy\Dropship\Model\ResourceModel\Vendor\Shipping;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Unirgy\Dropship\Model\Vendor\Shipping', 'Unirgy\Dropship\Model\ResourceModel\Vendor\Shipping');
        parent::_construct();
    }

    public function addVendorFilter($vendorId)
    {
        $this->getSelect()->where('vendor_id=?', $vendorId);
        return $this;
    }

    public function joinShipping()
    {
        $this->getSelect()->join(
            array('shipping'=>$this->getTable('shipping')),
            'shipping.shipping_id=main_table.shipping_id',
            array('shipping_code', 'shipping_title')
        );
        return $this;
    }
}