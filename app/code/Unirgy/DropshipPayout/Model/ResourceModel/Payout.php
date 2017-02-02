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
 
namespace Unirgy\DropshipPayout\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Unirgy\DropshipPayout\Model\Payout as ModelPayout;
use Unirgy\Dropship\Model\ResourceModel\Vendor\Statement\AbstractStatement;

class Payout extends AbstractStatement
{
    protected function _construct()
    {
        $this->_init('udropship_payout', 'payout_id');
    }
    
    protected function _getRowTable()
    {
        return $this->getTable('udropship_payout_row');
    }
    protected function _getAdjustmentTable()
    {
        return $this->getTable('udropship_payout_adjustment');
    }
    
    public function initAdjustmentsCollection($statement)
    {
        $adjCollection = $this->_hlp->createObj('\Unirgy\DropshipPayout\Model\ResourceModel\Payout\Adjustment\Collection');
        $adjCollection->addFieldToFilter('payout_id', $statement->getId());
        return $statement->setAdjustmentsCollection($adjCollection);
    }
    
    protected function _cleanRowTable($statement)
    {
        $poIds = [];
        $orders = $statement->getOrders();
        if (empty($orders)) {
            $poIds = [false];
        } else {
            foreach ($orders as $order) {
                $poIds[] = $order['po_id'];
            }
        }
        $conn = $this->getConnection();
        $conn->delete(
            $this->_getRowTable(), 
            $conn->quoteInto('payout_id=?', $statement->getId())
            .$conn->quoteInto(' AND (po_id not in (?)', $poIds)
            .$conn->quoteInto(' OR po_type!=? OR po_id is NULL)', $statement->getPoType())
        );
        return $this;
    }
    
    protected function _cleanAdjustmentTable($statement)
    {
        $conn = $this->getConnection();
        $conn->delete(
            $this->_getAdjustmentTable(), 
            $conn->quoteInto('payout_id=?', $statement->getId())
            .$conn->quoteInto(' AND adjustment_id not like ?', $this->_hlp->getAdjustmentPrefix('statement:payout').'%')
            .$conn->quoteInto(' AND adjustment_id not like ?', $this->_hlp->getAdjustmentPrefix('payout').'%')
        );
        return $this;
    }
    
    protected function _beforeDelete(AbstractModel $object)
    {
        $this->_cleanPayout($object);
        return parent::_beforeDelete($object);
    }
    
    protected function _getCleanExcludePoSelect(AbstractModel $object)
    {
        $conn = $this->getConnection();
        $excludePoSelect = $conn->select()->union([
            $conn->select()
                ->from(['pr' => $this->getTable('udropship_payout_row')], [])
                ->where('pr.po_type=?', $object->getPoType())
                ->where('pr.payout_id!=?', $object->getId())
                ->columns('pr.po_id')
        ]);
        $excludePoSelect->union([
            $conn->select()
                ->from(['sr' => $this->getTable('udropship_vendor_statement_row')], [])
                ->where('sr.po_type=?', $object->getPoType())
                ->columns('sr.po_id')
        ]);
        return $excludePoSelect;
    }
    
    protected function _cleanPayout(AbstractModel $object)
    {
        return $this->_cleanStatement($object);
    }
    
    protected function _cleanStatement(AbstractModel $object)
    {
        $conn = $this->getConnection();
        $conn->delete(
            $this->getTable('udropship_payout_row'),
            $conn->quoteInto('payout_id=?', $object->getId())
        );
        $excludePoSelect = $conn->select()->union([
            $conn->select()
                ->from(['pr' => $this->getTable('udropship_payout_row')], [])
                ->where('pr.po_type=?', $object->getPoType())
                ->where('pr.payout_id!=?', $object->getId())
                ->columns('pr.po_id')
        ]);
        $this->_changePosAttribute(array_keys($object->getOrders()), $object->getPoType(), 'payout_id', NULL, $excludePoSelect);
        $this->_changePosAttribute(array_keys($object->getOrders()), $object->getPoType(), 'udropship_payout_status', NULL, $excludePoSelect);
        parent::_cleanStatement($object);
        return $this;
    }
    
    protected function _prepareAdjustmentSave($statement, $adjustment)
    {
        $adjustment['payout_id'] = $statement->getId();
        $adjustment['statement_id'] = $statement->getStatementId();
        return parent::_prepareAdjustmentSave($statement, $adjustment);
    }
    
    protected function _prepareRowSave($statement, $row)
    {
        $row['payout_id'] = $statement->getId();
        $row['statement_id'] = $statement->getStatementId();
        return parent::_prepareRowSave($statement, $row);
    }

    protected function _afterSave(AbstractModel $object)
    {
       
        parent::_afterSave($object);
        
        if ($object->getPayoutStatus() == ModelPayout::STATUS_CANCELED) {
            $this->_cleanPayout($object);
            return $this;
        }
        
        $this->_saveRows($object);
        $this->_saveAdjustments($object);
        
        if ($object->getOrders()) {
            $this->_changePosAttribute(array_keys($object->getOrders()), $object->getPoType(), 'udropship_payout_status', $object->getPayoutStatus());
            $this->_changePosAttribute(array_keys($object->getOrders()), $object->getPoType(), 'payout_id', $object->getId());
        }
        
        return $this;
    }
}
