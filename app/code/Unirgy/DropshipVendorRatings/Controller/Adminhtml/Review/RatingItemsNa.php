<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Review;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\LayoutFactory;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class RatingItemsNa extends AbstractReview
{
    public function execute()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_RAW)->setContents($this->_view->getLayout()->createBlock('Unirgy\DropshipVendorRatings\Block\Adminhtml\Review\Rating\DetailedNa')->setIndependentMode()->toHtml());
    }
}
