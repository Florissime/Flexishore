<?php

namespace Unirgy\DropshipVendorRatings\Model\ResourceModel\Review;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Review\Model\ResourceModel\Review\Summary as ReviewSummary;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class Summary extends ReviewSummary
{
    /**
     * @var HelperData
     */
    protected $_rateHlp;

    public function __construct(Context $context, 
        HelperData $helperData)
    {
        $this->_rateHlp = $helperData;

        parent::__construct($context);
    }

    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);
           $select->where('entity_type=?',$this->_rateHlp->useEt());
        return $select;
    }
    public function reAggregate($summary)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable())
            ->where('entity_type=?',$this->_rateHlp->useEt())
            ->group(['entity_pk_value', 'store_id']);
        foreach ($this->getConnection()->fetchAll($select) as $row) {
            if (isset($summary[$row['store_id']]) && isset($summary[$row['store_id']][$row['entity_pk_value']])) {
                $summaryItem = $summary[$row['store_id']][$row['entity_pk_value']];
                if ($summaryItem->getCount()) {
                    $ratingSummary = round($summaryItem->getSum() / $summaryItem->getCount());
                } else {
                    $ratingSummary = $summaryItem->getSum();
                }
            } else {
                $ratingSummary = 0;
            }
            $this->getConnection()->update(
                $this->getMainTable(),
                ['rating_summary' => $ratingSummary],
                $this->getConnection()->quoteInto('primary_id = ?', $row['primary_id'])
            );
        }
        return $this;
    }
}