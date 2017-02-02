<?php

namespace Unirgy\DropshipMicrositePro\Controller\Vendor;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\ScopeInterface;

abstract class AbstractVendor extends Action
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var DesignInterface
     */
    protected $_viewDesign;

    /**
     * @var RawFactory
     */
    protected $_resultRawFactory;

    /**
     * @var EncoderInterface
     */
    protected $_jsonEncoder;

    /** @var \Unirgy\Dropship\Helper\Data  */
    protected $_hlp;

    /** @var \Unirgy\DropshipMicrositePro\Helper\Data  */
    protected $_mspHlp;

    /** @var \Unirgy\DropshipMicrosite\Helper\Data  */
    protected $_msHlp;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        DesignInterface $viewDesignInterface, 
        RawFactory $resultRawFactory,
        EncoderInterface $jsonEncoder,
        \Unirgy\DropshipMicrosite\Helper\Data $micrositeHelper,
        \Unirgy\DropshipMicrositePro\Helper\Data $micrositeProHelper,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_viewDesign = $viewDesignInterface;
        $this->_resultRawFactory = $resultRawFactory;
        $this->_jsonEncoder = $jsonEncoder;
        $this->resultPageFactory = $resultPageFactory;
        $this->_hlp = $udropshipHelper;
        $this->_msHlp = $micrositeHelper;
        $this->_mspHlp = $micrositeProHelper;

        parent::__construct($context);
    }

    protected function _setTheme()
    {
        $theme = $this->_hlp->getScopeConfig('udropship/vendor/interface_theme');
        if (!empty($theme)) {
            $this->_viewDesign->setDesignTheme($theme);
        }
    }

    protected function _renderPage($handles=null, $active=null)
    {
        $this->_setTheme();
        $resultPage = $this->resultPageFactory->create(true);
        $resultPage->addHandle($handles);
        $resultPage->addHandle($resultPage->getDefaultLayoutHandle());

        $this->_hlp->getObj('\Magento\Framework\View\Page\Config')->addBodyClass('udropship-vendor');

        if ($active && ($head = $resultPage->getLayout()->getBlock('header'))) {
            $head->setActivePage($active);
        }
        $resultPage->getLayout()->initMessages();
        $resultPage->renderResult($this->_response);
        $html = $this->_response->getBody();
        //$newHtml = preg_replace('`<link[^>]+?media/styles.css[^>]+?/>`', '', $html);
        //$newHtml = preg_replace('`<link[^>]+?css/styles-m.css[^>]+?/>`', '', $newHtml);
        //$newHtml = preg_replace('`<link[^>]+?styles-l.css[^>]+?/>`', '', $newHtml);
        //$this->_response->setBody($newHtml);
        return $this->_response;
    }
    public function returnResult($result)
    {
        return $this->_resultRawFactory->create()->setContents($this->_jsonEncoder->encode($result));
    }
    protected function _getSession()
    {
        return ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
    }
}