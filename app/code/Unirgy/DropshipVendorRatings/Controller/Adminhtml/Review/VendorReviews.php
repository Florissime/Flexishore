<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Review;

use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;

class VendorReviews extends AbstractReview
{
    public function execute()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_RAW)->setContents(
            $this->_view->getLayout()
                ->createBlock('\Unirgy\DropshipVendorRatings\Block\Adminhtml\Review\Grid', 'admin.vendor.reviews')
                ->setVendorId($this->getRequest()->getParam('id'))
                ->setUisMassactionAvailable(false)
                ->setUseAjax(true)
                ->toHtml()
        );
    }
}
