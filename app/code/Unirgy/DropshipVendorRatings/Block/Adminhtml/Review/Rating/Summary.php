<?php

namespace Unirgy\DropshipVendorRatings\Block\Adminhtml\Review\Rating;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Rating\Option\VoteFactory;

class Summary extends Template
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var VoteFactory
     */
    protected $_optionVoteFactory;

    /**
     * @var RatingFactory
     */
    protected $_ratingFactory;

    public function __construct(Context $context, 
        Registry $frameworkRegistry, 
        VoteFactory $optionVoteFactory, 
        RatingFactory $modelRatingFactory, 
        array $data = [])
    {
        $this->_coreRegistry = $frameworkRegistry;
        $this->_optionVoteFactory = $optionVoteFactory;
        $this->_ratingFactory = $modelRatingFactory;

        parent::__construct($context, $data);

        $this->setTemplate('Magento_Review::rating/stars/summary.phtml');
        $this->setReviewId($this->_coreRegistry->registry('review_data')->getId());
    }

    public function getRating()
    {
        if( !$this->getRatingCollection() ) {
            $ratingCollection = $this->_optionVoteFactory->create()
                ->getResourceCollection()
                ->setReviewFilter($this->getReviewId())
                ->addRatingInfo()
                ->load();
            $this->setRatingCollection( ( $ratingCollection->getSize() ) ? $ratingCollection : false );
        }
        return $this->getRatingCollection();
    }

    public function getRatingSummary()
    {
        if( !$this->getRatingSummaryCache() ) {
            $this->setRatingSummaryCache($this->_ratingFactory->create()->getReviewSummary($this->getReviewId()));
        }

        return $this->getRatingSummaryCache();
    }
}
