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
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipMicrosite\Helper\ProtectedCode;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Unirgy\DropshipMicrosite\Helper\Data as DropshipMicrositeHelperData;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Helper\ProtectedCode as DropshipHelperProtectedCode;
use Unirgy\Dropship\Model\VendorFactory;

class Context
{
    /**
     * @var LoggerInterface
     */
    public $_logger;

    /**
     * @var StoreManagerInterface
     */
    public $_storeManager;

    /**
     * @var Registry
     */
    public $_registry;

    /**
     * @var HelperData
     */
    public $_hlp;

    /**
     * @var RequestInterface
     */
    public $_request;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var DropshipMicrositeHelperData
     */
    public $_msHlp;

    /**
     * @var VendorFactory
     */
    public $_vendorFactory;

    /**
     * @var ManagerInterface
     */
    public $_eventManager;

    public $_url;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $modelStoreManagerInterface,
        \Magento\Framework\Registry $frameworkRegistry,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Framework\App\RequestInterface $appRequestInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $configScopeConfigInterface,
        \Unirgy\DropshipMicrosite\Helper\Data $dropshipMicrositeHelperData,
        \Unirgy\Dropship\Model\VendorFactory $modelVendorFactory,
        \Magento\Framework\Event\ManagerInterface $eventManagerInterface,
        \Magento\Framework\UrlInterface $url
    )
    {
        $this->_logger = $logger;
        $this->_storeManager = $modelStoreManagerInterface;
        $this->_registry = $frameworkRegistry;
        $this->_hlp = $udropshipHelper;
        $this->_request = $appRequestInterface;
        $this->scopeConfig = $configScopeConfigInterface;
        $this->_msHlp = $dropshipMicrositeHelperData;
        $this->_vendorFactory = $modelVendorFactory;
        $this->_eventManager = $eventManagerInterface;
        $this->_url = $url;
    }
}