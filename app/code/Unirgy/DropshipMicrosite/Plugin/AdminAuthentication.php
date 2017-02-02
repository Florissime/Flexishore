<?php

namespace Unirgy\DropshipMicrosite\Plugin;

class AdminAuthentication
{
    /**
     * @var \Magento\Backend\Model\Auth
     */
    protected $_auth;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_url;

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $_response;

    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $_actionFlag;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    /**
     * @var \Magento\Backend\App\BackendAppList
     */
    protected $backendAppList;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    protected $cookieManager;

    public function __construct(
        \Magento\Backend\Model\Auth $auth,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
    ) {
        $this->_auth = $auth;
        $this->_url = $url;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->cookieManager = $cookieManager;
    }
    public function aroundDispatch(
        \Magento\Backend\App\AbstractAction $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $user = $this->_auth->getUser();
        if (!$user || !$user->getId()) {
            if ($this->cookieManager->getCookie('udvendor_portal')) {
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setUrl($this->_url->getUrl('udropship/vendor/login'));
            }
        }
        return $proceed($request);
    }
}