<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Review;

use Magento\Backend\App\Action\Context;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\ReviewFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class Post extends AbstractReview
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var RatingFactory
     */
    protected $_ratingFactory;

    public function __construct(
        StoreManagerInterface $modelStoreManagerInterface,
        RatingFactory $modelRatingFactory,
        Context $context,
        HelperData $helperData,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Review\Model\ReviewFactory $reviewFactory
    )
    {
        $this->_storeManager = $modelStoreManagerInterface;
        $this->_ratingFactory = $modelRatingFactory;

        parent::__construct($context, $helperData, $udropshipHelper, $reviewFactory);
    }

    public function execute()
    {
        $vendorId = $this->getRequest()->getParam('vendor_id', false);
        $shipmentId = $this->getRequest()->getParam('shipment_id', false);
        if ($data = (array)$this->getRequest()->getPost()) {
            if(isset($data['select_stores'])) {
                $data['stores'] = $data['select_stores'];
            }

            $review = $this->_reviewFactory->create()->setData($data);

            try {
                $review->setEntityId($this->_rateHlp->myEt()) // product
                    ->setEntityPkValue($vendorId)
                    ->setRelEntityPkValue($shipmentId)
                    ->setStoreId($this->_storeManager->getDefaultStoreView()->getId())
                    ->setStatusId($data['status_id'])
                    ->setCustomerId(null)//null is for administrator only
                    ->save();

                $arrRatingId = $this->getRequest()->getParam('ratings', []);
                foreach ($arrRatingId as $ratingId=>$optionId) {
                    $this->_ratingFactory->create()
                       ->setRatingId($ratingId)
                       ->setReviewId($review->getId())
                       ->addOptionVote($optionId, $vendorId);
                }

                $review->aggregate();

                $this->messageManager->addSuccess(__('The review has been saved.'));
                if( $this->getRequest()->getParam('ret') == 'pending' ) {
                    $this->getResponse()->setRedirect($this->getUrl('*/*/pending'));
                } else {
                    $this->getResponse()->setRedirect($this->getUrl('*/*/'));
                }

                return;
            } catch (\Exception $e){
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->getResponse()->setRedirect($this->getUrl('*/*/'));
        return;
    }
}
