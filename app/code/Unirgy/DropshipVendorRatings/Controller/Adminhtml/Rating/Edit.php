<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Rating;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Rating\EntityFactory;

class Edit extends AbstractRating
{
    /**
     * @var RatingFactory
     */
    protected $_ratingFactory;

    /**
     * @var LayoutFactory
     */
    protected $_viewLayoutFactory;

    public function __construct(Context $context, 
        Registry $frameworkRegistry, 
        EntityFactory $ratingEntityFactory, 
        RatingFactory $modelRatingFactory
    )
    {
        $this->_ratingFactory = $modelRatingFactory;

        parent::__construct($context, $frameworkRegistry, $ratingEntityFactory);
    }

    public function execute()
    {
        $resultPage = $this->_initAction();
        $title = $resultPage->getConfig()->getTitle();

        $ratingModel = $this->_ratingFactory->create();
        if ($this->getRequest()->getParam('id')) {
            $ratingModel->load($this->getRequest()->getParam('id'));
        }

        $title->prepend($ratingModel->getId()
            ? $ratingModel->getRatingCode()
            : __('New Rating'));

        return $resultPage->addContent($this->_view->getLayout()->createBlock('\Unirgy\DropshipVendorRatings\Block\Adminhtml\Rating\Edit'))
            ->addLeft($this->_view->getLayout()->createBlock('\Unirgy\DropshipVendorRatings\Block\Adminhtml\Rating\Edit\Tabs'));
    }
}
