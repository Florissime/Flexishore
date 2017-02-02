<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Report;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\View\LayoutFactory;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Index extends AbstractReport
{
    public function execute()
    {
        $page = $this->_initAction();
        $page->addBreadcrumb(__('Shipment Details'), __('Shipment Details'));
        $title = $page->getConfig()->getTitle();
        $title->prepend(__('Shipment Details'));
        return $page->addContent($page->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\Report'));
    }
}
