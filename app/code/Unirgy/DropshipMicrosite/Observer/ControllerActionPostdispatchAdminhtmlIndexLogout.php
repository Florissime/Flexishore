<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ControllerActionPostdispatchAdminhtmlIndexLogout extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        if (!$this->_vendorId) {
            return;
        }
        /** @var \Magento\Framework\UrlInterface $urlBuilder */
        $urlBuilder = ObjectManager::getInstance()->get('Magento\Framework\UrlInterface');
        $coreSession = ObjectManager::getInstance()->get('Magento\Framework\Model\Session');
        $oId = $coreSession->getSessionId();
        $sId = !empty($_COOKIE['frontend']) ? $_COOKIE['frontend'] : null;

        $this->_switchSession(\Magento\Framework\App\Area::AREA_FRONTEND, $sId);
        $session = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
        if ($session->isLoggedIn() && $session->getId()==$this->_vendorId) {
            $session->setId(null);
        }

        if (!empty($_SESSION['core']['last_url'])) {
            $url = $_SESSION['core']['last_url'];
        } elseif (!empty($_SESSION['core']['visitor_data']['http_referer'])) {
            $url = $_SESSION['core']['visitor_data']['http_referer'];
        } else {
            $url = $urlBuilder->getUrl('udropship', ['_store'=>'default']);
        }
        if (false !== strpos($url, 'ajax')) {
            $url = $urlBuilder->getUrl('udropship', ['_store'=>'default']);
        } elseif (false !== strpos($url, 'cms/index/noRoute')) {
            $url = $urlBuilder->getUrl('udropship', ['_store'=>'default']);
        }
        $this->_switchSession(\Magento\Framework\App\Area::AREA_ADMINHTML, $oId, true);

        header("Location: ".$url);
        exit;
    }
}
