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
 * @package    Unirgy_DropshipMicrosite
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Backend\Block\Store\Switcher;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipMicrosite\Helper\Data as HelperData;
use Unirgy\Dropship\Helper\Item;

abstract class AbstractObserver
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\HTTP\Header
     */
    protected $_httpHeader;

    /**
     * @var \Unirgy\Dropship\Helper\Data
     */
    protected $_hlp;

    /**
     * @var Item
     */
    protected $_iHlp;

    /**
     * @var \Unirgy\DropshipMicrosite\Helper\Data
     */
    protected $_msHlp;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Unirgy\DropshipMicrosite\Helper\Data $micrositeHelper,
        \Unirgy\Dropship\Helper\Item $helperItem
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->_httpHeader = $httpHeader;
        $this->_hlp = $udropshipHelper;
        $this->_msHlp = $micrositeHelper;
        $this->_iHlp = $helperItem;

    }

    protected $_vendorPassword;

    protected function _initConfigRewrites()
    {
        return ;
        if ($this->scopeConfig->isSetFlag('udropship/microsite/filter_vendor_categories', ScopeInterface::SCOPE_STORE)) {
            Mage::getConfig()->setNode('global/models/catalog_resource/rewrite/category_tree', 'Unirgy\DropshipMicrosite\Model\ResourceModel\CategoryTree');
            Mage::getConfig()->setNode('global/models/catalog_resource/rewrite/category_flat', 'Unirgy\DropshipMicrosite\Model\ResourceModel\CategoryFlat');
        }
        Mage::getConfig()->setNode('global/blocks/catalog/rewrite/navigation', 'Unirgy\DropshipMicrosite\Block\CatalogNavigation');
        Mage::getConfig()->setNode('global/blocks/page/rewrite/html_topmenu', 'Unirgy\DropshipMicrosite\Block\PageTopmenu');
    }


    protected function _getAccessAllowOrigin()
    {
        $url = $this->_httpHeader->getHttpReferer();
        $parsed = @parse_url($url);
        if (isset($parsed['scheme']) && isset($parsed['host']) && ($vendor = $this->_msHlp->getUrlFrontendVendor($url))) {
            return sprintf('%s://%s', $parsed['scheme'], $parsed['host']);
        }
        return false;
    }

    protected function _switchSession($area, $id=null, $restore=false)
    {
        $session = $this->_hlp->session();
        $session->writeClose();
        $GLOBALS['_SESSION'] = null;
        if ($restore) {
            $this->_hlp->setDesignStore();
        } else {
            $this->_hlp->setDesignStore(true, $area);
        }
        if ($id) {
            $session->setSessionId($id);
        }
        $session->start();
    }

    protected $_vendorId;

    protected function _getVendor()
    {
        return $this->_msHlp->getCurrentVendor();
    }

    protected function _limitStoreSwitcher($block)
    {
        if ($block instanceof Switcher && ($v = $this->_getVendor())
            && (($staging = $this->scopeConfig->getValue('udropship/microsite/staging_website', ScopeInterface::SCOPE_STORE)) || ($lw = $v->getLimitWebsites()))
        ) {
            $block->setWebsiteIds($staging ? (array)$staging : (is_array($lw) ? $lw : explode(',', $lw)));
        }
    }

    protected function _catalog_product_type_prepare_cart_options($observer)
    {
        $iHlp = $this->_iHlp;
        $product = $observer->getProduct();
        $refUrl = $this->_httpHeader->getHttpReferer();
        $vendor = $this->_msHlp->getUrlFrontendVendor($refUrl);
        $stickUdms = $this->scopeConfig->getValue('udropship/stock/stick_microsite', ScopeInterface::SCOPE_STORE);
        if ($vendor && ($vId = $vendor->getId()) && $stickUdms>0) {
            if (in_array($stickUdms, [1,2])) {
                $iHlp->setForcedVendorIdOption($product, $vId);
                $iHlp->setSkipStockCheckVendorOption($product, 1);
            } elseif (in_array($stickUdms, [3,4])) {
                $iHlp->setPriorityVendorIdOption($product, $vId);
            }
        }
    }

}