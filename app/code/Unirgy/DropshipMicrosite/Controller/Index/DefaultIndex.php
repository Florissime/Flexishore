<?php

namespace Unirgy\DropshipMicrosite\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\LayoutFactory;
use Unirgy\DropshipMicrosite\Helper\Data as HelperData;

class DefaultIndex extends AbstractIndex
{
    /**
     * @var HelperData
     */
    protected $_msHlp;

    public function __construct(
        Context $context,
        HelperData $helperData
    )
    {
        $this->_msHlp = $helperData;

        parent::__construct($context);
    }

    public function execute()
    {
        $vendor = $this->_msHlp->getCurrentVendor();
        if ($vendor) {
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
            $resultPage->getConfig()->getTitle()->set($this->_msHlp->getLandingPageTitle());
            $resultPage->getConfig()->setKeywords($this->_msHlp->getLandingPageKeywords());
            $resultPage->getConfig()->setDescription($this->_msHlp->getLandingPageDescription());
            return $resultPage;
        }
        $this->_forward('index', 'index', 'cms');
    }
}
