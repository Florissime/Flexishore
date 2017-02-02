<?php

namespace Unirgy\DropshipShippingClass\Controller\Adminhtml\Vendor;

class Index extends AbstractVendor
{
    public function execute()
    {
        $page = $this->_initAction();
        $title = $page->getConfig()->getTitle();

        $title->prepend(__('Sales'));
        $title->prepend(__('Dropship'));
        $title->prepend(__('Vendor Ship Classes'));

        return $page->addContent($page->getLayout()->createBlock('Unirgy\DropshipShippingClass\Block\Adminhtml\Vendor'));
    }
}
