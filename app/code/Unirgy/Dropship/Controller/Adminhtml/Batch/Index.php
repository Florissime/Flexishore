<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Batch;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\View\LayoutFactory;

class Index extends AbstractBatch
{
    public function execute()
    {
        $page = $this->_initAction();
        $title = $page->getConfig()->getTitle();

        $title->prepend(__('Sales'));
        $title->prepend(__('Dropship'));
        $title->prepend(__('Label Batches'));

        return $page->addContent($page->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\Batch'));
    }
}
