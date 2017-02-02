<?php

namespace Unirgy\DropshipVendorRatings\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Review\Model\ResourceModel\Rating as ResourceModelRating;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorRatings\Helper\Data as DropshipVendorRatingsHelperData;
use Unirgy\Dropship\Helper\Data as HelperData;

class Rating extends ResourceModelRating
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var DropshipVendorRatingsHelperData
     */
    protected $_rateHlp;

    public function __construct(
        HelperData $helperData,
        DropshipVendorRatingsHelperData $dropshipVendorRatingsHelperData, 
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Review\Model\ResourceModel\Review\Summary $reviewSummary,
        $connectionName = null
    )
    {
        $this->_hlp = $helperData;
        $this->_rateHlp = $dropshipVendorRatingsHelperData;
        parent::__construct($context, $logger, $moduleManager, $storeManager, $reviewSummary, $connectionName);
    }

    protected function _initUniqueFields()
    {
        $this->_uniqueFields = [[
            'field' => ['rating_code','entity_id'],
            'title' => /* __('Rating with the same title')*/ ''
        ]];
        return $this;
    }
    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);
        return $select;
    }
    protected function _afterDelete(AbstractModel $object)
    {
        $this->_rateHlp->useEt($object->getEntityId());
        parent::_afterDelete($object);
        $this->_rateHlp->resetEt();
        return $this;
    }
    protected function _getEntitySummaryData($object)
    {
        $read = $this->getConnection();
        $sql = "SELECT
                    {$this->getTable('rating_option_vote')}.entity_pk_value as entity_pk_value,
                    SUM({$this->getTable('rating_option_vote')}.percent) as sum,
                    COUNT(*) as count,
                    {$this->getTable('review_store')}.store_id
                FROM
                    {$this->getTable('rating_option_vote')}
                INNER JOIN
                    {$this->getTable('review')}
                    ON {$this->getTable('rating_option_vote')}.review_id={$this->getTable('review')}.review_id
                LEFT JOIN
                    {$this->getTable('review_store')}
                    ON {$this->getTable('rating_option_vote')}.review_id={$this->getTable('review_store')}.review_id
                INNER JOIN
                    {$this->getTable('rating_store')} AS rst
                    ON rst.rating_id = {$this->getTable('rating_option_vote')}.rating_id AND rst.store_id = {$this->getTable('review_store')}.store_id
                INNER JOIN
                    {$this->getTable('review_status')} AS review_status
                    ON {$this->getTable('review')}.status_id = review_status.status_id
                INNER JOIN
                    {$this->getTable('rating')} AS rt
                    ON rt.rating_id = {$this->getTable('rating_option_vote')}.rating_id AND rt.is_aggregate=1
                WHERE ";
        if ($object->getEntityPkValue()) {
            $sql .= "{$read->quoteInto($this->getTable('rating_option_vote').'.entity_pk_value=?', $object->getEntityPkValue())} AND ";
        }
        $sql .= $read->quoteInto("{$this->getTable('review')}.entity_id = ? AND ", $this->_rateHlp->useEt());
        $sql .= "review_status.status_code = 'approved'
                GROUP BY
                    {$this->getTable('rating_option_vote')}.entity_pk_value, {$this->getTable('review_store')}.store_id";

        return $read->fetchAll($sql);
    }
    public function getReviewSummary($object, $onlyForCurrentStore = true)
    {
        $read = $this->getConnection();
        $sql = "SELECT
                    SUM({$this->getTable('rating_option_vote')}.percent) as sum,
                    COUNT(*) as count,
                    {$this->getTable('review_store')}.store_id
                FROM
                    {$this->getTable('rating_option_vote')}
                LEFT JOIN
                    {$this->getTable('review_store')}
                    ON {$this->getTable('rating_option_vote')}.review_id={$this->getTable('review_store')}.review_id
                INNER JOIN
                    {$this->getTable('rating_store')} AS rst
                    ON rst.rating_id = {$this->getTable('rating_option_vote')}.rating_id AND rst.store_id = {$this->getTable('review_store')}.store_id
                INNER JOIN
                    {$this->getTable('rating')} AS rt
                    ON rt.rating_id = {$this->getTable('rating_option_vote')}.rating_id AND rt.is_aggregate=1
                WHERE
                    {$read->quoteInto($this->getTable('rating_option_vote').'.review_id=?', $object->getReviewId())}
                GROUP BY
                    {$this->getTable('rating_option_vote')}.review_id, {$this->getTable('review_store')}.store_id";

        $data = $read->fetchAll($sql);

        if($onlyForCurrentStore) {
            foreach ($data as $row) {
                if($row['store_id']==$this->_storeManager->getStore()->getId()) {
                    $object->addData( $row );
                }
            }
            return $object;
        }

        $result = [];

        $stores = $this->_storeManager->getStore()->getResourceCollection()->load();

        foreach ($data as $row) {
            $clone = clone $object;
            $clone->addData( $row );
            $result[$clone->getStoreId()] = $clone;
        }

        $usedStoresId = array_keys($result);

        foreach ($stores as $store) {
               if (!in_array($store->getId(), $usedStoresId)) {
                   $clone = clone $object;
                $clone->setCount(0);
                $clone->setSum(0);
                $clone->setStoreId($store->getId());
                $result[$store->getId()] = $clone;

               }
        }

        return array_values($result);
    }
}