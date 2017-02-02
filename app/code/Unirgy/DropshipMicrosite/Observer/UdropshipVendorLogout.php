<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\User\Model\UserFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdropshipVendorLogout extends AbstractObserver implements ObserverInterface
{
    /**
     * @var UserFactory
     */
    protected $_userFactory;

    public function __construct(
        UserFactory $userFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Unirgy\DropshipMicrosite\Helper\Data $micrositeHelper,
        \Unirgy\Dropship\Helper\Item $helperItem
    )
    {
        $this->_userFactory = $userFactory;

        parent::__construct($scopeConfig, $httpHeader, $udropshipHelper, $micrositeHelper, $helperItem);
    }

    public function execute(Observer $observer)
    {
        $vendor = $observer->getEvent()->getVendor();
        $user = $this->_userFactory->create()->load($vendor->getId(), 'udropship_vendor');

        if ($user->getId() && !empty($_COOKIE['adminhtml'])) {
            $coreSession = ObjectManager::getInstance()->get('Magento\Framework\Model\Session');
            $oId = $coreSession->getSessionId();
            $sId = $_COOKIE['adminhtml'];
            $this->_switchSession(\Magento\Framework\App\Area::AREA_ADMINHTML, $sId);
            $session = ObjectManager::getInstance()->get('Magento\Backend\Model\Session');
            if ($session->isLoggedIn() && $session->getUser()->getId()==$user->getId()) {
                ObjectManager::getInstance()->get('Magento\Backend\Model\Session')->unsetAll();
                ObjectManager::getInstance()->get('Magento\Backend\Model\Session')->unsetAll();
            }
            $this->_switchSession(\Magento\Framework\App\Area::AREA_FRONTEND, $oId, true);
        }
    }
}
