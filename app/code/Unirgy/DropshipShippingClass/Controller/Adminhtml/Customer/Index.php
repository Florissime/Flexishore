<?php

namespace Unirgy\DropshipShippingClass\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\LayoutFactory;

class Index extends AbstractCustomer
{
    public function execute()
    {
        $page = $this->_initAction();
        $title = $page->getConfig()->getTitle();

        $title->prepend(__('Sales'));
        $title->prepend(__('Dropship'));
        $title->prepend(__('Customer Ship Classes'));

        return $page->addContent($page->getLayout()->createBlock('Unirgy\DropshipShippingClass\Block\Adminhtml\Customer'));
    }
}
