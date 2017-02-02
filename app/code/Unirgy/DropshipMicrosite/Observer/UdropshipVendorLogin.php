<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Backend\Model\Url;
use Magento\User\Model\UserFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdropshipVendorLogin extends AbstractObserver implements ObserverInterface
{
    /**
     * @var UserFactory
     */
    protected $_userFactory;

    /**
     * @var Url
     */
    protected $_backendUrl;

    /**
     * @var Cookie
     */
    protected $cookieManager;

    public function __construct(
        UserFactory $userFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Unirgy\DropshipMicrosite\Helper\Data $micrositeHelper,
        \Unirgy\Dropship\Helper\Item $helperItem
    )
    {
        $this->_userFactory = $userFactory;
        $this->_backendUrl = $backendUrl;
        $this->cookieManager = $cookieManager;

        parent::__construct($scopeConfig, $httpHeader, $udropshipHelper, $micrositeHelper, $helperItem);
    }

    public function execute(Observer $observer)
    {
        $vendor = $observer->getEvent()->getVendor();
        $user = $this->_userFactory->create()->load($vendor->getId(), 'udropship_vendor');
        if ($user->getId() && $vendor->getShowProductsMenuItem()) {
            $coreSession = ObjectManager::getInstance()->get('Magento\Framework\Model\Session');
            $oId = $coreSession->getSessionId();
            $sId = !empty($_COOKIE['adminhtml']) ? $_COOKIE['adminhtml'] : $oId;
            $this->_switchSession(\Magento\Framework\App\Area::AREA_ADMINHTML, $sId);
            /** @var \Magento\Backend\Model\Auth $auth */
            $auth = ObjectManager::getInstance()->get('\Magento\Backend\Model\Auth');
            if (!$auth->isLoggedIn()) {
                $user->getResource()->recordLogin($user);
                $auth->setIsFirstVisit(true);
                $auth->getAuthStorage()->setUser($user);
                $auth->getAuthStorage()->processLogin();
                if ($this->_backendUrl->useSecretKey()) {
                    $this->_backendUrl->renewSecretUrls();
                }
            }
            $this->cookieManager->set('udvendor_portal', 1, 0);
            $this->_switchSession(\Magento\Framework\App\Area::AREA_FRONTEND, $oId, true);
        }
    }
}
