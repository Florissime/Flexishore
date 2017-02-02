<?php

namespace Unirgy\DropshipMicrositePro\Controller\Index;

use Magento\Cms\Helper\Page;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipMicrosite\Helper\Data as HelperData;

class LandingPage extends AbstractIndex
{
    /**
     * @var HelperData
     */
    protected $_msHlp;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Page
     */
    protected $_helperPage;

    public function __construct(Context $context, 
        HelperData $helperData, 
        ScopeConfigInterface $scopeConfig,
        Page $helperPage)
    {
        $this->_msHlp = $helperData;
        $this->_scopeConfig = $scopeConfig;
        $this->_helperPage = $helperPage;

        parent::__construct($context);
    }

    public function execute()
    {
        $vendor = $this->_msHlp->getCurrentVendor();
        if ($vendor) {
            $defPageId = $this->_scopeConfig->getValue('web/default/umicrosite_default_landingpage', ScopeInterface::SCOPE_STORE);
            $vPageId = $vendor->getVendorLandingPage();
            if (!($resultPage = $this->_helperPage->prepareResultPage($this, $vPageId))) {
                if (!($resultPage = $this->_helperPage->prepareResultPage($this, $defPageId))) {
                    $this->_forward('default', 'index', 'umicrosite');
                    return;
                }
            }
            $resultPage->getConfig()->getTitle()->set($this->_msHlp->getLandingPageTitle());
            $resultPage->getConfig()->setKeywords($this->_msHlp->getLandingPageKeywords());
            $resultPage->getConfig()->setDescription($this->_msHlp->getLandingPageDescription());
            return $resultPage;
        }
        $this->_forward('index', 'index', 'cms');
    }
}
