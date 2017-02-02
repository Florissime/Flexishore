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

use \Magento\Framework\Model\AbstractModel;
use \Unirgy\Dropship\Model\ResourceModel\Vendor\Statement\AbstractStatement;

class Statement extends AbstractStatement
{
    protected function _construct()
    {
        $this->_init('udropship_vendor_statement', 'vendor_statement_id');
    }
    
    protected function _getAdjustmentTable()
    {
        return $this->getTable('udropship_vendor_statement_adjustment');
    }
    protected function _getRowTable()
    {
        return $this->getTable('udropship_vendor_statement_row');
    }
    protected function _getRefundRowTable()
    {
        return $this->getTable('udropship_vendor_statement_refund_row');
    }
    
    protected function _prepareRowSave($statement, $row)
    {
        $row['statement_id'] = $statement->getId();
        return parent::_prepareRowSave($statement, $row);
    }
    protected function _prepareRefundRowSave($statement, $row)
    {
        $row['statement_id'] = $statement->getId();
        $row['row_json'] = \Zend_Json::encode($row);
        $row = array_merge($row, $row['amounts']);
        return $row;
    }
    
    public function initAdjustmentsCollection($statement)
    {
        $statement->setAdjustmentsCollection(
            $this->_hlp->createObj('\Unirgy\Dropship\Model\ResourceModel\Vendor\Statement\Adjustment\Collection')
                ->addFieldToFilter('statement_id', $statement->getStatementId())
        );
        return $this;
    }
    
    protected function _cleanRowTable($statement)
    {
        $poIds = array();
        $orders = $statement->getOrders();
        if (empty($orders)) {
            $poIds = array(false);
        } else {
            foreach ($orders as $order) {
                $poIds[] = $order['po_id'];
            }
        }
        $conn = $this->getConnection();
        $conn->delete(
            $this->_getRowTable(), 
            $conn->quoteInto('statement_id=?', $statement->getId())
            .$conn->quoteInto(' AND (po_id not in (?)', $poIds)
            .$conn->quoteInto(' OR po_type!=? OR po_id is NULL)', $statement->getPoType())
        );
        return $this;
    }
    protected function _cleanRefundRowTable($statement)
    {
        $conn = $this->getConnection();
        $conn->delete(
            $this->_getRefundRowTable(),
            $conn->quoteInto('statement_id=?', $statement->getId())
        );
        return $this;
    }
    
    protected function _cleanAdjustmentTable($statement)
    {
        $conn = $this->getConnection();
        $conn->delete(
            $this->_getAdjustmentTable(),
            $conn->quoteInto('statement_id=?', $statement->getStatementId())
            .$conn->quoteInto(' AND adjustment_id not like ?', $this->_hlp->getAdjustmentPrefix('statement').'%')
        );
        return $this;
    }
    protected function _saveRefundRows(AbstractModel $object)
    {
        $this->_cleanRefundRowTable($object);
        if ($object->getRefunds()) {
            $rows = array();
            $rawRows = array();
            foreach ($object->getRefunds() as $refund) {
                $_row = $this->_prepareTableInsert($this->_getRefundRowTable(), $this->_prepareRefundRowSave($object, $refund), false);
                foreach ($_row as $_r) {
                    $rawRows[] = $_r;
                }
                $rows[] = implode(',', array_fill(0, count($_row), '?'));
            }
            $this->getConnection()->query(sprintf(
                'INSERT INTO %s (%s) VALUES (%s) %s',
                $this->_getRefundRowTable(), implode(',', $this->getTableColumns($this->_getRefundRowTable())), implode('),(', $rows),
                $this->_hlp->createOnDuplicateExpr($this->getConnection(), $this->getTableColumns($this->_getRefundRowTable()))
            ), $rawRows);
        }
        return $this;
    }

    protected function _cleanAdjustmentTableFull($statement)
    {
        $conn = $this->getConnection();
        $conn->delete(
            $this->_getAdjustmentTable(),
            $conn->quoteInto('statement_id=?', $statement->getStatementId())
        );
        return $this;
    }
    
    protected function _beforeDelete(AbstractModel $object)
    {
        if ($this->_hlp->isUdpayoutActive()) {
            $ptCollection = $this->_hlp->createObj('\Unirgy\DropshipPayout\Model\ResourceModel\Payout\Collection')
                ->addFieldToFilter('statement_id', $object->getStatementId())
                ->addFieldToFilter('payout_status', \Unirgy\DropshipPayout\Model\Payout::STATUS_HOLD);
            foreach ($ptCollection as $pt) {
                $pt->setPayoutStatus($pt->getData('before_hold_status'))->save();
            }
        }
        $this->_cleanStatement($object);
        $this->_cleanAdjustmentTableFull($object);
        return parent::_beforeDelete($object);
    }
    
    protected function _afterSave(AbstractModel $object)
    {
       
        parent::_afterSave($object);
        
        $this->_saveRows($object);
        $this->_saveRefundRows($object);
        $this->_saveAdjustments($object);
        
        if ($object->getOrders()) {
            $this->_changePosAttribute(array_keys($object->getOrders()), $object->getPoType(), 'statement_id', $object->getStatementId());
        }
        
        return $this;
    }
    
    protected function _getCleanExcludePoSelect(AbstractModel $object)
    {
        $conn = $this->getConnection();
        $excludePoSelect = $conn->select()->union(array(
            $conn->select()
                ->from(array('sr' => $this->getTable('udropship_vendor_statement_row')), array())
                ->where('sr.po_type=?', $object->getPoType())
                ->where('sr.statement_id!=?', $object->getId())
                ->columns('sr.po_id')
        ));
        if ($this->_hlp->isUdpayoutActive()) {
            $excludePoSelect->union(array(
                $conn->select()
                    ->from(array('pr' => $this->getTable('udropship_payout_row')), array())
                    ->where('pr.po_type=?', $object->getPoType())
                    ->columns('pr.po_id')
            ));
        }
        return $excludePoSelect;
    }
    
    protected function _cleanStatement(AbstractModel $object)
    {
        $conn = $this->getConnection();
        $conn->delete(
            $this->getTable('udropship_vendor_statement_row'),
            $conn->quoteInto('statement_id=?', $object->getId())
        );
        $excludePoSelect = $conn->select()->union(array(
            $conn->select()
                ->from(array('sr' => $this->getTable('udropship_vendor_statement_row')), array())
                ->where('sr.po_type=?', $object->getPoType())
                ->where('sr.statement_id!=?', $object->getId())
                ->columns('sr.po_id')
        ));
        $this->_changePosAttribute(array_keys($object->getOrders()), $object->getPoType(), 'statement_id', NULL, $excludePoSelect);
        parent::_cleanStatement($object);
        return $this;
    }
    
    public function markPosHold($statement)
    {
        return $this->_changePosAttribute(array_keys($statement->getUnpaidOrders()), $statement->getPoType(), 'udropship_payout_status', \Unirgy\DropshipPayout\Model\Payout::STATUS_HOLD);
    }
    
    public function markPosPaid($statement)
    {
        return $this->_changePosAttribute(array_keys($statement->getUnpaidOrders()), $statement->getPoType(), 'udropship_payout_status', \Unirgy\DropshipPayout\Model\Payout::STATUS_PAID);
    }
    
    protected function _prepareAdjustmentSave($statement, $adjustment)
    {
        $adjustment['statement_id'] = $statement->getStatementId();
        return parent::_prepareAdjustmentSave($statement, $adjustment);
    }
}
