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

namespace Unirgy\Dropship\Model\ResourceModel\Vendor;

use \Magento\CatalogInventory\Model\Stock;
use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use \Magento\Framework\Model\ResourceModel\Db\Context;
use \Unirgy\Dropship\Helper\Data as HelperData;

class NotifyLowstock extends AbstractDb
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(
        Context $context,
        HelperData $helper
    )
    {
        $this->_hlp = $helper;

        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('udropship_vendor_lowstock', 'id');
    }
    
    public function vendorCleanLowstock()
    {
        $conn = $this->getConnection();
        $idsToDel = $conn->fetchCol($conn->select()
                ->from(array('vls' => $this->getTable('udropship_vendor_lowstock')), array('id'))
                ->join(array('uv' => $this->getTable('udropship_vendor')), 'vls.vendor_id=uv.vendor_id', array())
                ->join(array('cisi' => $this->getTable('cataloginventory_stock_item')),
                    $conn->quoteInto('cisi.product_id=vls.product_id AND cisi.stock_id=?',Stock::DEFAULT_STOCK_ID), array())
                ->joinLeft(array('uvp' => $this->getTable('udropship_vendor_product')), 'uvp.vendor_id=vls.vendor_id and uvp.product_id=vls.product_id', array())
                ->where("uvp.vendor_product_id is not null AND (uvp.stock_qty is not null AND uvp.stock_qty>uv.notify_lowstock_qty"
                	." OR uvp.stock_qty is null AND uv.vendor_id!=?)", $this->_hlp->getLocalVendorId())
                ->orWhere("uvp.vendor_product_id is not null AND uvp.stock_qty is null"
                    ." AND uv.vendor_id=? AND cisi.qty>uv.notify_lowstock_qty", $this->_hlp->getLocalVendorId())
                ->orWhere("uvp.vendor_product_id is null AND cisi.qty>uv.notify_lowstock_qty")
        );
        $conn->delete($this->getTable('udropship_vendor_lowstock'), $conn->quoteInto('id in (?)', $idsToDel));
    }
    
    public function markLowstockNotified($select, $columns)
    {
        $this->getConnection()->query(sprintf(
            'INSERT INTO %s (%s) %s %s',
            $this->getTable('udropship_vendor_lowstock'), implode(',', array_keys($columns)), $select,
            $this->_hlp->createOnDuplicateExpr($this->getConnection(), array_keys($columns))
        ));
    }
}