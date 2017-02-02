<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Cms\Model\PageFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CmsPagePrepareSave extends AbstractObserver implements ObserverInterface
{
    /**
     * @var PageFactory
     */
    protected $_pageFactory;

    public function __construct(
        PageFactory $pageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Unirgy\DropshipMicrosite\Helper\Data $micrositeHelper,
        \Unirgy\Dropship\Helper\Item $helperItem
    )
    {
        $this->_pageFactory = $pageFactory;

        parent::__construct($scopeConfig, $httpHeader, $udropshipHelper, $micrositeHelper, $helperItem);
    }

    /**
    * Deny access to unauthorized cms page edits and set vendor id before save
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        $page = $observer->getEvent()->getPage();
        $vendor = $this->_getVendor();
        if ($page && $vendor) {
            if ($page->getId()) {
                $origPage = $this->_pageFactory->create()->load($page->getId());
                if ($origPage->getUdropshipVendor()!=$vendor->getId()) {
                    throw new \Exception('Access denied');
                }
            }
            $page->setUdropshipVendor($vendor->getId());
        }
    }
}
