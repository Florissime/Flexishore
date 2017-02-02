<?php

namespace Unirgy\DropshipVendorRatings\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Json\EncoderInterface;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Review;
use Magento\Review\Model\ReviewFactory;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class Post extends AbstractCustomer
{
    /**
     * @var ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var RatingFactory
     */
    protected $_ratingFactory;

    public function __construct(
        ReviewFactory $modelReviewFactory,
        StoreManagerInterface $modelStoreManagerInterface, 
        RatingFactory $modelRatingFactory,
        Context $context,
        HelperData $helperData,
        \Unirgy\Dropship\Helper\Data $udropshipHelper
    )
    {
        $this->_reviewFactory = $modelReviewFactory;
        $this->_storeManager = $modelStoreManagerInterface;
        $this->_ratingFactory = $modelRatingFactory;

        parent::__construct($context, $helperData, $udropshipHelper);
    }

    public function execute()
    {
        if (!$this->_validatePost()) {
            $this->messageManager->addError(__('Review not allowed.'));
            $this->_redirect('*/*/pending');
            return $this;
        }
        if ($data = $this->_fetchFormData()) {
            $rating = [];
            if (isset($data['ratings']) && is_array($data['ratings'])) {
                $rating = $data['ratings'];
            }
        } else {
            $data   = (array)$this->getRequest()->getPost();
            $rating = $this->getRequest()->getParam('ratings', []);
        }

        if (!empty($data)) {
            $session    = ObjectManager::getInstance()->get('Unirgy\DropshipVendorRatings\Model\Session');
            $review     = $this->_reviewFactory->create()->setData($data);
            $validate = $review->validate();
            if ($validate === true) {
                try {
                    $review->setEntityId($this->_rateHlp->myEt())
                        ->setEntityPkValue($this->getRequest()->getParam('id'))
                        ->setRelEntityPkValue($this->getRequest()->getParam('rel_id'))
                        ->setStatusId(Review::STATUS_PENDING)
                        ->setCustomerId(ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomerId())
                        ->setStoreId($this->_storeManager->getStore()->getId())
                        ->setStores([$this->_storeManager->getStore()->getId()])
                        ->save();

                    foreach ($rating as $ratingId => $optionId) {
                        $this->_ratingFactory->create()
                        ->setRatingId($ratingId)
                        ->setReviewId($review->getId())
                        ->setCustomerId(ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomerId())
                        ->addOptionVote($optionId, $this->getRequest()->getParam('id'));
                    }

                    $review->aggregate();
                    $this->messageManager->addSuccess(__('Your review has been accepted for moderation.'));
                }
                catch (\Exception $e) {
                    $this->_saveFormData($data);
                    $this->messageManager->addError(__('Unable to post the review.'));
                }
            }
            else {
                $this->_saveFormData($data);
                if (is_array($validate)) {
                    foreach ($validate as $errorMessage) {
                        $this->messageManager->addError($errorMessage);
                    }
                }
                else {
                    $this->messageManager->addError(__('Unable to post the review.'));
                }
            }
        }

        $this->_redirect(
            count($this->_rateHlp->getPendingCustomerReviewsCollection())>0
            ? '*/*/pending' : '*/*/index'
        );
    }
}
