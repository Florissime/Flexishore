<?php

namespace Unirgy\DropshipVendorRatings\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\ResourceModel\Rating\Option;
use Magento\Review\Model\ResourceModel\Review as ResourceModelReview;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class Review extends ResourceModelReview
{
    /**
     * @var HelperData
     */
    protected $_rateHlp;

    public function __construct(Context $context, 
        DateTime $date, 
        StoreManagerInterface $storeManager, 
        RatingFactory $ratingFactory, 
        Option $ratingOptions, 
        HelperData $helperData)
    {
        $this->_rateHlp = $helperData;

        parent::__construct($context, $date, $storeManager, $ratingFactory, $ratingOptions);
    }

    public function getTotalReviews($entityPkValue, $approvedOnly=false, $storeId=0)
    {
        $select = $this->getConnection()->select()
            ->from($this->_reviewTable, "COUNT(*)")
            ->where("{$this->_reviewTable}.entity_id = ?", $this->_rateHlp->useEt())
            ->where("{$this->_reviewTable}.entity_pk_value = ?", $entityPkValue);

        if($storeId > 0) {
            $select->join(['store'=>$this->_reviewStoreTable],
                $this->_reviewTable.'.review_id=store.review_id AND store.store_id=' . (int)$storeId, []);
        }
        if( $approvedOnly ) {
            $select->where("{$this->_reviewTable}.status_id = ?", 1);
        }
        return $this->getConnection()->fetchOne($select);
    }
}