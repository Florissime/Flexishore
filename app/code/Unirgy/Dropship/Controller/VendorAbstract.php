<?php

namespace Unirgy\Dropship\Controller;

use \Magento\Backend\Model\View\Result\ForwardFactory;
use \Magento\Framework\App\Action\Action;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Registry;
use \Magento\Framework\View\DesignInterface;
use \Magento\Framework\View\LayoutFactory;
use \Magento\Store\Model\StoreManagerInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;

abstract class VendorAbstract extends Action
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DesignInterface
     */
    protected $_viewDesign;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var LayoutFactory
     */
    protected $_viewLayoutFactory;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var ForwardFactory
     */
    protected $_resultForwardFactory;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    protected $_resultRawFactory;
    protected $_httpHeader;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        DesignInterface $viewDesignInterface, 
        StoreManagerInterface $storeManager,
        LayoutFactory $viewLayoutFactory, 
        Registry $registry,
        ForwardFactory $resultForwardFactory,
        HelperData $helper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\HTTP\Header $httpHeader
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->_viewDesign = $viewDesignInterface;
        $this->_storeManager = $storeManager;
        $this->_viewLayoutFactory = $viewLayoutFactory;
        $this->_registry = $registry;
        $this->_resultForwardFactory = $resultForwardFactory;
        $this->_hlp = $helper;
        $this->resultPageFactory = $resultPageFactory;
        $this->_resultRawFactory = $resultRawFactory;
        $this->_httpHeader = $httpHeader;

        parent::__construct($context);
    }

    protected function _getSession()
    {
        return $this->_hlp->session();
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
        $resultPage->addHandle($resultPage->getDefaultLayoutHandle());
        $resultPage->addHandle($handles);

        $title = $resultPage->getConfig()->getTitle();
        $title->prepend(__('Dropship Vendor Interface'));

        $this->_hlp->getObj('\Magento\Framework\View\Page\Config')->addBodyClass('udropship-vendor');

        if ($active && ($head = $resultPage->getLayout()->getBlock('udropship.header'))) {
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

    /**
     * Action predispatch
     *
     * Check customer authentication for some actions
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        $r = $request;

        if ($r->isDispatched()) {
            $action = $r->getActionName();
            $session = $this->_hlp->session();

            if (!$session->isLoggedIn() && !$this->_registry->registry('udropship_login_checked')) {
                $this->_registry->register('udropship_login_checked', true);
                if ($r->getPost('login')) {
                    $login = $this->getRequest()->getPost('login');
                    if (!empty($login['username']) && !empty($login['password'])) {
                        try {
                            if (!$session->login($login['username'], $login['password'])) {
                                $this->messageManager->addError(__('Invalid username or password.'));
                            }
                            $session->setUsername($login['username']);
                        } catch (\Exception $e) {
                            $this->messageManager->addError($e->getMessage());
                        }
                    } else {
                        $this->messageManager->addError(__('Login and password are required'));
                    }
                    if ($session->isLoggedIn()) {
                        $this->_loginPostRedirect();
                    }
                }
                if (!preg_match('#^(login|logout|password)#i', $action)) {
                    $forward = $this->_resultForwardFactory->create();
                    $forward->setModule($this->_hlp->getRouteFrontName('udropship'));
                    $forward->setController('vendor');
                    //return $forward->forward('login');
                    return $this->_forward('login','vendor','udropship');
                }
            } else {
                /*
                if ($this->_hlp->isModuleActive('Unirgy_DropshipVendorPortalUrl')) {
                    Mage::getConfig()->setNode('global/models/core/rewrite/url', 'Unirgy\DropshipVendorPortalUrl\Model\Url');
                } else {
                    Mage::getConfig()->setNode('global/models/core/rewrite/url', 'Unirgy\Dropship\Model\Url');
                }
                */
            }
        }
        return parent::dispatch($request);
    }

    protected function _loginPostRedirect()
    {
        $this->_getSession()->loginPostRedirect($this);
    }
    protected function _forward($action, $controller = null, $module = null, array $params = null)
    {
        if (!is_null($module)) {
            $module = $this->_hlp->getRouteFrontName($module);
        }
        return parent::_forward($action, $controller, $module, $params);
    }

}