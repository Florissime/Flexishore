<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Report;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\View\LayoutFactory;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Item extends AbstractReport
{
    public function execute()
    {
        $page = $this->_initAction();
        $page->addBreadcrumb(__('Shipment Item Details'), __('Shipment Item Details'));
        $title = $page->getConfig()->getTitle();
        $title->prepend(__('Shipment Item Details'));
        return $page->addContent($page->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\ReportItem'));
    }
}
