<?php

namespace Unirgy\DropshipVendorRatings\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Json\EncoderInterface;
use Magento\Review\Model\ReviewFactory;
use Magento\Sales\Model\Order\ShipmentFactory;
use Psr\Log\LoggerInterface;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class Vote extends AbstractCustomer
{
    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $_cookieManager;

    /**
     * @var ReviewFactory
     */
    protected $_reviewFactory;

    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        ReviewFactory $modelReviewFactory,
        Context $context,
        HelperData $helperData,
        \Unirgy\Dropship\Helper\Data $udropshipHelper
    )
    {
        $this->_cookieManager = $cookieManager;
        $this->_reviewFactory = $modelReviewFactory;

        parent::__construct($context, $helperData, $udropshipHelper);
    }

    public function execute() {
        $customerSession = ObjectManager::getInstance()->get('Magento\Customer\Model\Session');

        try {
            $id = $this->getRequest()->getParam('id');
            $value = $this->getRequest()->getParam('value');

            $customerId = (int)$customerSession->getCustomerId();

            $votedUdreviews = $customerSession->getVotedUdreviews();

            if (!$votedUdreviews) {
                $votedUdreviews = $this->_cookieManager->getCookie('udratings_votes_' . $customerId);
            }

            $rHlp = $this->_hlp->rHlp();

            if ($votedUdreviews && in_array($id, explode(',', $votedUdreviews))
            ) {
                return $this->returnResult([
                    'error' => true,
                    'message' => __('You have already voted on this review!')
                ]);
            } else {
                $reviewModel = $this->_reviewFactory->create();
                $reviewData = $rHlp->loadDbColumns($reviewModel, $id, ['helpfulness_yes','helpfulness_no']);
                reset($reviewData);
                $reviewData = current($reviewData);
                if (!empty($reviewData)) {
                    if ($value) {
                        $reviewData['helpfulness_yes']++;
                    } else {
                        $reviewData['helpfulness_no']++;
                    }
                    $reviewData['helpfulness_pcnt'] = $reviewData['helpfulness_yes']/($reviewData['helpfulness_yes']+$reviewData['helpfulness_no'])*100;
                    $rHlp->updateModelData($reviewModel,$reviewData,$id);
                    $votedUdreviews = $votedUdreviews . ($votedUdreviews ? ',' : '') . $id;
                    $customerSession->setVotedUdreviews($votedUdreviews);
                    $this->_cookieManager->setCookie('udratings_votes_' . $customerId, $votedUdreviews);
                    return $this->returnResult([
                        'message' => __('Your voice has been accepted. Thank you!')
                    ]);
                } else {
                    return $this->returnResult([
                        'error' => true,
                        'message' => __('Review was not found!')
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->_hlp->logError($e);
            return $this->returnResult([
                'error' => true,
                'message' => __('Unable to vote. Please, try again later.')
            ]);
        }
    }
}
