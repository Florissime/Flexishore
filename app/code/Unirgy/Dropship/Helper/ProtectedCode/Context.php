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

namespace Unirgy\Dropship\Helper\ProtectedCode;

use \Magento\Framework\DataObject;
use \Magento\Sales\Model\Order as ModelOrder;
use \Magento\Shipping\Model\Carrier\AbstractCarrier;
use \Magento\Shipping\Model\Config;
use \Magento\Shipping\Model\Shipping;
use \Unirgy\Dropship\Model\Source;
use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use \Magento\Catalog\Model\Product\Type\AbstractType as ProductTypeAbstract;
use \Unirgy\SimpleLicense\Helper\ProtectedCode as SimpleLicenseProtectedCode;

class Context
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    public $_eventManager;

    /**
     * @var \Unirgy\Dropship\Helper\Item
     */
    public $_iHlp;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $_storeManager;

    /**
     * @var \Unirgy\Dropship\Helper\Data
     */
    public $_hlp;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $_logger;

    /**
     * @var \Magento\Framework\Registry
     */
    public $_registry;

    /**
     * @var Config
     */
    public $_shippingConfig;

    /**
     * @var Shipping
     */
    public $_magentoShippingFactory;

    /**
     * @var Source
     */
    public $_src;

    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    public $_orderConverter;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Unirgy\Dropship\Helper\Item $udropshipItemHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Shipping\Model\Config $shippingConfig,
        \Magento\Shipping\Model\ShippingFactory $magentoShippingFactory,
        \Unirgy\Dropship\Model\Source $source,
        \Magento\Sales\Model\Convert\Order $orderConverter,
        \Magento\Framework\Locale\FormatInterface $localeFormat
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->_eventManager = $eventManager;
        $this->_storeManager = $storeManager;
        $this->_iHlp = $udropshipItemHelper;
        $this->_hlp = $udropshipHelper;
        $this->_src = $source;
        $this->_logger = $logger;
        $this->_registry = $registry;
        $this->_shippingConfig = $shippingConfig;
        $this->_magentoShippingFactory = $magentoShippingFactory;
        $this->_orderConverter = $orderConverter;
        $this->_localeFormat = $localeFormat;
    }
}