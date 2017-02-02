<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

class NewAction extends AbstractStatement
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            __('Generate'),
            __('Generate')
        );
        $resultPage->getConfig()->getTitle()->prepend(
            __('Generate')
        );
        return $resultPage;
    }
}
