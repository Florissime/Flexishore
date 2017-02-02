<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Review;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\LayoutFactory;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class Edit extends AbstractReview
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        $title = $resultPage->getConfig()->getTitle();

        $ratingModel = $this->_reviewFactory->create();
        if ($this->getRequest()->getParam('id')) {
            $ratingModel->load($this->getRequest()->getParam('id'));
        }

        $title->prepend($ratingModel->getId()
            ? $ratingModel->getTitle()
            : __('New Rating'));

        return $resultPage->addContent($this->_view->getLayout()->createBlock('\Unirgy\DropshipVendorRatings\Block\Adminhtml\Review\Edit'));
    }
}
