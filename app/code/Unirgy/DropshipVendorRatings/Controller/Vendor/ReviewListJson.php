<?php

namespace Unirgy\DropshipVendorRatings\Controller\Vendor;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\LayoutFactory;

class ReviewListJson extends AbstractVendor
{
    public function execute()
    {
        $layout = $this->_viewLayoutFactory->create();
        $update = $layout->getUpdate();
        $update->load('udratings_vendor_review_listreview');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $this->resultFactory->create(ResultFactory::TYPE_RAW)->setContents($output);
    }
}
