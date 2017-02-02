<?php

namespace Unirgy\DropshipVendorRatings\Block\Adminhtml\Review\Rating;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Rating\Option\VoteFactory;

class DetailedNa extends Template
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var RatingFactory
     */
    protected $_ratingFactory;

    /**
     * @var VoteFactory
     */
    protected $_optionVoteFactory;

    protected $_voteCollection = false;
    public function __construct(Context $context, 
        Registry $frameworkRegistry, 
        RatingFactory $modelRatingFactory, 
        VoteFactory $optionVoteFactory, 
        array $data = [])
    {
        $this->_coreRegistry = $frameworkRegistry;
        $this->_ratingFactory = $modelRatingFactory;
        $this->_optionVoteFactory = $optionVoteFactory;

        parent::__construct($context, $data);
        $this->setTemplate('Unirgy_DropshipVendorRatings::udratings/rating/detailed_na.phtml');
        if( $this->_coreRegistry->registry('review_data') ) {
            $this->setReviewId($this->_coreRegistry->registry('review_data')->getReviewId());
        }
    }

    public function getRating()
    {
        if( !$this->getRatingCollection() ) {
            if( $this->_coreRegistry->registry('review_data') ) {
                $stores = $this->_coreRegistry->registry('review_data')->getStores();

                $stores = array_diff($stores, [0]);

                $ratingCollection = $this->_ratingFactory->create()
                    ->getResourceCollection()
                    ->addEntityFilter('udropship_vendor')
                    ->addFieldToFilter('is_aggregate', 0)
                    ->setStoreFilter($stores)
                    ->setPositionOrder()
                    ->load()
                    ->addOptionToItems();

                $this->_voteCollection = $this->_optionVoteFactory->create()
                    ->getResourceCollection()
                    ->setReviewFilter($this->getReviewId())
                    ->addOptionInfo()
                    ->load()
                    ->addRatingOptions();

            } elseif (!$this->getIsIndependentMode()) {
                $ratingCollection = $this->_ratingFactory->create()
                    ->getResourceCollection()
                    ->addEntityFilter('udropship_vendor')
                    ->addFieldToFilter('is_aggregate', 0)
                    ->setStoreFilter(null)
                    ->setPositionOrder()
                    ->load()
                    ->addOptionToItems();
            } else {
                 $ratingCollection = $this->_ratingFactory->create()
                    ->getResourceCollection()
                    ->addEntityFilter('udropship_vendor')
                    ->addFieldToFilter('is_aggregate', 0)
                    ->setStoreFilter($this->getRequest()->getParam('select_stores') ? $this->getRequest()->getParam('select_stores') : $this->getRequest()->getParam('stores'))
                    ->setPositionOrder()
                    ->load()
                    ->addOptionToItems();


            }
            $this->setRatingCollection( ( $ratingCollection->getSize() ) ? $ratingCollection : false );
        }
        return $this->getRatingCollection();
    }

    public function setIndependentMode()
    {
        $this->setIsIndependentMode(true);
        return $this;
    }

    public function isSelected($option, $rating)
    {
        if($this->getIsIndependentMode()) {
            $ratings = $this->getRequest()->getParam('ratings');

            if(isset($ratings[$option->getRatingId()])) {
                return $option->getId() == $ratings[$option->getRatingId()];
            }

            return false;
        }

        if($this->_voteCollection) {
            foreach($this->_voteCollection as $vote) {
                if($option->getId() == $vote->getOptionId()) {
                    return true;
                }
            }
        }

        return false;
    }
}
