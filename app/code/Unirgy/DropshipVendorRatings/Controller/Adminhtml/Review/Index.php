<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Review;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\LayoutFactory;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class Index extends AbstractReview
{
    public function execute()
    {
        $page = $this->_initAction();
        return $page->addContent($page->getLayout()->createBlock('Unirgy\DropshipVendorRatings\Block\Adminhtml\Review\Main'));
    }
}
