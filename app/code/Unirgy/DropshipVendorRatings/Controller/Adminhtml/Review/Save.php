<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Review;

use Magento\Backend\App\Action\Context;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Rating\Option\VoteFactory;
use Magento\Review\Model\ReviewFactory;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class Save extends AbstractReview
{
    /**
     * @var ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * @var VoteFactory
     */
    protected $_optionVoteFactory;

    /**
     * @var RatingFactory
     */
    protected $_ratingFactory;

    public function __construct(
        VoteFactory $optionVoteFactory,
        RatingFactory $modelRatingFactory,
        Context $context,
        HelperData $helperData,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Review\Model\ReviewFactory $reviewFactory
    )
    {
        $this->_optionVoteFactory = $optionVoteFactory;
        $this->_ratingFactory = $modelRatingFactory;

        parent::__construct($context, $helperData, $udropshipHelper, $reviewFactory);
    }

    public function execute()
    {
        if (($data = (array)$this->getRequest()->getPost()) && ($reviewId = $this->getRequest()->getParam('id'))) {
            $review = $this->_reviewFactory->create()->load($reviewId);

            if (! $review->getId()) {
                $this->messageManager->addError(__('The review was removed by another user or does not exist.'));
            } else {
                try {
                    $review->addData($data)->save();

                    $arrRatingId = $this->getRequest()->getParam('ratings', []);
                    $votes = $this->_optionVoteFactory->create()
                        ->getResourceCollection()
                        ->setReviewFilter($reviewId)
                        ->addOptionInfo()
                        ->load()
                        ->addRatingOptions();
                    foreach ($arrRatingId as $ratingId=>$optionId) {
                        if($vote = $votes->getItemByColumnValue('rating_id', $ratingId)) {
                            $this->_ratingFactory->create()
                                ->setVoteId($vote->getId())
                                ->setReviewId($review->getId())
                                ->updateOptionVote($optionId);
                        } else {
                            $this->_ratingFactory->create()
                                ->setRatingId($ratingId)
                                ->setReviewId($review->getId())
                                ->addOptionVote($optionId, $review->getEntityPkValue());
                        }
                    }

                    $review->aggregate();

                    $this->messageManager->addSuccess(__('The review has been saved.'));
                } catch (\Exception $e){
                    $this->messageManager->addError($e->getMessage());
                }
            }

            return $this->getResponse()->setRedirect($this->getUrl($this->getRequest()->getParam('ret') == 'pending' ? '*/*/pending' : '*/*/'));
        }
        $this->_redirect('/');
    }
}
