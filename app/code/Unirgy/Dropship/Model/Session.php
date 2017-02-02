<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    \Unirgy\Dropship
 * @copyright  Copyright (c) 2015-2016 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\Dropship\Model;

use \Magento\Framework\App\Action\Action;
use \Magento\Framework\Event\ManagerInterface;
use \Magento\Framework\UrlInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Session extends \Magento\Framework\Session\SessionManager
{
    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var UrlInterface
     */
    protected $_url;

    protected $_vendor;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    public function __construct(
        \Magento\Framework\Registry $registry,
        ManagerInterface $eventManager,
        HelperData $helperData, 
        UrlInterface $url,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Session\SidResolverInterface $sidResolver,
        \Magento\Framework\Session\Config\ConfigInterface $sessionConfig,
        \Magento\Framework\Session\SaveHandlerInterface $saveHandler,
        \Magento\Framework\Session\ValidatorInterface $validator,
        \Magento\Framework\Session\StorageInterface $storage,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\App\State $appState
    )
    {
        $this->_registry = $registry;
        $this->_eventManager = $eventManager;
        $this->_hlp = $helperData;
        $this->_url = $url;

        parent::__construct(
            $request,
            $sidResolver,
            $sessionConfig,
            $saveHandler,
            $validator,
            $storage,
            $cookieManager,
            $cookieMetadataFactory,
            $appState
        );

        $this->_eventManager->dispatch('udropship_session_init', array('session'=>$this));
        if ($this->isLoggedIn()) {
            $this->_registry->register('isSecureArea', true, true);
        }
    }

    public function setVendor($vendor)
    {
        $this->_vendor = $vendor;
        return $this;
    }

    public function getVendor()
    {
        if ($this->_vendor instanceof Vendor) {
            return $this->_vendor;
        }

        if ($this->getId()) {
            $vendor = $this->_hlp->getVendor($this->getId());
        } else {
            /* @var \Unirgy\Dropship\Model\Vendor $vendor*/
            $vendor = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor');
        }
        $this->setVendor($vendor);

        return $this->_vendor;
    }

    public function getVendorId()
    {
        return $this->getId();
    }

    public function isLoggedIn()
    {
        return (bool)$this->getId() && (bool)$this->getVendor()->getId();
    }


    public function setVendorAsLoggedIn($vendor)
    {
        $this->setVendor($vendor);
        $this->setId($vendor->getId());
        $this->_eventManager->dispatch('udropship_vendor_login', array('vendor'=>$vendor));
        return $this;
    }

    public function login($username, $password)
    {
        /* @var \Unirgy\Dropship\Model\Vendor $vendor*/
        $vendor = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor');

        if ($vendor->authenticate($username, $password)) {
            $this->setVendorAsLoggedIn($vendor);
            return true;
        }
        return false;
    }

    public function loginById($vendorId)
    {
        /* @var \Unirgy\Dropship\Model\Vendor $vendor*/
        $vendor = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor');
        if ($vendor->getId()) {
            $this->setVendorAsLoggedIn($vendor);
            return true;
        }
        return false;
    }

    public function logout()
    {
        if ($this->isLoggedIn()) {
            $this->setId(null);
            $this->_eventManager->dispatch('udropship_vendor_logout', array('vendor'=>$this->getVendor()));
        }
        return $this;
    }

    public function authenticate(Action $action, $loginUrl = null)
    {
        if (!$this->isLoggedIn()) {
            $this->setBeforeAuthUrl($this->_url->getUrl('*/*/*', array('_current'=>true)));
            if (is_null($loginUrl)) {
                $loginUrl = $this->_url->getUrl('udropship/vendor/login');
            }
            $action->getResponse()->setRedirect($loginUrl);
            return false;
        }
        return true;
    }

    public function loginPostRedirect($action)
    {
        if (!$this->getBeforeAuthUrl() || $this->getBeforeAuthUrl() == $this->_url->getBaseUrl() ) {
            $this->setBeforeAuthUrl($this->_url->getUrl('udropship/vendor'));
            if ($this->isLoggedIn()) {
                if ($action->getRequest()->getActionName()=='noRoute') {
                    $this->setBeforeAuthUrl($this->_url->getUrl('*/*'));
                } else {
                    $this->setBeforeAuthUrl($this->_url->getUrl('*/*/*', array('_current'=>true)));
                }
                if ($this->getAfterAuthUrl()) {
                    $this->setBeforeAuthUrl($this->getAfterAuthUrl(true));
                }
            }
        } else if ($this->getBeforeAuthUrl() == $this->_url->getUrl('udropship/vendor/logout')) {
            $this->setBeforeAuthUrl($this->_url->getUrl('udropship/vendor'));
        } else {
            if (!$this->getAfterAuthUrl()) {
                $this->setAfterAuthUrl($this->getBeforeAuthUrl());
            }
            if ($this->isLoggedIn()) {
                $this->setBeforeAuthUrl($this->getAfterAuthUrl(true));
            }
        }
        $action->getResponse()->setRedirect($this->getBeforeAuthUrl(true));
    }
}
