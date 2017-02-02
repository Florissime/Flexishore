<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Rating;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Review\Model\Rating\EntityFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\Model\View\Result\Page;

abstract class AbstractRating extends Action
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var EntityFactory
     */
    protected $_ratingEntityFactory;

    public function __construct(Context $context, 
        Registry $frameworkRegistry, 
        EntityFactory $ratingEntityFactory)
    {
        $this->_coreRegistry = $frameworkRegistry;
        $this->_ratingEntityFactory = $ratingEntityFactory;

        parent::__construct($context);
    }

    protected function _initEnityId()
    {
        $this->_coreRegistry->register('entityId', $this->_ratingEntityFactory->create()->getIdByCode('udropship_vendor'));
        return $this;
    }

    protected function _initAction()
    {
        $this->_initEnityId();
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu("Unirgy_DropshipVendorRatings::rating")
            ->addBreadcrumb(__('Sales'), __('Sales'))
            ->addBreadcrumb(__('Dropship'), __('Dropship'))
            ->addBreadcrumb(__('Reviews and Ratings'), __('Reviews and Ratings'))
            ->addBreadcrumb(__('Manage Ratings'), __('Manage Ratings'));
        $title = $resultPage->getConfig()->getTitle();
        $title->prepend(__('Sales'));
        $title->prepend(__('Dropship'));
        $title->prepend(__('Reviews and Ratings'));
        $title->prepend(__('Manage Ratings'));
        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Unirgy_DropshipVendorRatings::rating');
    }

}
