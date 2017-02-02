<?php

namespace Unirgy\DropshipMicrosite\Controller\Index;

class Vendors extends AbstractIndex
{

    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Vendors'));
        return $resultPage;
    }
}
