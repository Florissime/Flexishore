<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Rating;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Review\Model\Rating\EntityFactory;

class Index extends AbstractRating
{
    public function execute()
    {
        $page = $this->_initAction();

        return $page->addContent($page->getLayout()->createBlock('Unirgy\DropshipVendorRatings\Block\Adminhtml\Rating\Rating'));
    }
}
