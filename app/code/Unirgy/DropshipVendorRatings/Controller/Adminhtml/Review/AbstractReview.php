<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Review;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\Model\View\Result\Page;

abstract class AbstractReview extends Action
{
    /**
     * @var HelperData
     */
    protected $_rateHlp;

    protected $_hlp;

    protected $_reviewFactory;

    public function __construct(
        Context $context,
        HelperData $helperData,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Review\Model\ReviewFactory $reviewFactory
    )
    {
        $this->_rateHlp = $helperData;
        $this->_hlp = $udropshipHelper;
        $this->_reviewFactory = $reviewFactory;

        parent::__construct($context);
    }

    protected $_publicActions = ['edit'];

    protected function _initAction()
    {
        $this->_rateHlp->useMyEt();
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu("Unirgy_DropshipVendorRatings::rating")
            ->addBreadcrumb(__('Sales'), __('Sales'))
            ->addBreadcrumb(__('Dropship'), __('Dropship'))
            ->addBreadcrumb(__('Reviews and Ratings'), __('Reviews and Ratings'));
        $title = $resultPage->getConfig()->getTitle();
        $title->prepend(__('Sales'));
        $title->prepend(__('Dropship'));
        $title->prepend(__('Reviews and Ratings'));
        return $resultPage;
    }

    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
            case 'pending':
                return $this->_authorization->isAllowed('Unirgy_DropshipVendorRatings::review_pending');
                break;
            default:
                return $this->_authorization->isAllowed('Unirgy_DropshipVendorRatings::review_all');
                break;
        }
    }
}
