<?php

namespace Unirgy\DropshipMicrosite\Model;

use Magento\Config\Model\ResourceModel\Config\Data as ConfigData;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ResourceModel\Store as ResourceModelStore;
use Magento\Store\Model\Store as ModelStore;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;

class Store extends ModelStore
{
    protected $_oldUseVendorUrl=true;
    protected $_useVendorUrl=null;
    public function useVendorUrl($flag=null)
    {
        $result = $this->_useVendorUrl;
        if (!is_null($flag) && $this->_useVendorUrl!==$flag) {
            $this->_oldUseVendorUrl = $this->_useVendorUrl;
            $this->_useVendorUrl = $flag;
        } elseif (is_null($flag) && is_null($result)) {
            /** @var \Magento\Framework\Registry $registry */
            $registry = ObjectManager::getInstance()->get('\Magento\Framework\Registry');
            $result = $registry->registry('useVendorUrl') ? null : false;
        }
        return $result;
    }
    public function resetUseVendorUrl()
    {
        $this->_useVendorUrl = $this->_oldUseVendorUrl;
        return $this;
    }
    protected $_curMySecure;
    public function getBaseUrl($type = UrlInterface::URL_TYPE_LINK, $secure = null)
    {
        $this->_curMySecure = $secure;
        $cacheKey = $type . '/' . ($secure === null ? 'null' : ($secure ? 'true' : 'false'));
        if ($this->useVendorUrl() === true) {
            $cacheKey .= 'true';
        } elseif ($this->useVendorUrl() === false) {
            $cacheKey .= 'false';
        } else {
            $__uvu = $this->useVendorUrl();
            if ($__uvu instanceof \Unirgy\Dropship\Model\Vendor) {
                $__uvu = $__uvu->getId();
            }
            $cacheKey .= ($__uvu === null ? 'null' : $__uvu);
        }
        if (!isset($this->_baseUrlCache[$cacheKey])) {
            $secure = $secure === null ? $this->isCurrentlySecure() : (bool)$secure;
            switch ($type) {
                case UrlInterface::URL_TYPE_WEB:
                    $path = $secure
                        ? self::XML_PATH_SECURE_BASE_URL
                        : self::XML_PATH_UNSECURE_BASE_URL;
                    $url = $this->getConfig($path);
                    break;

                case UrlInterface::URL_TYPE_LINK:
                    $path = $secure ? self::XML_PATH_SECURE_BASE_LINK_URL : self::XML_PATH_UNSECURE_BASE_LINK_URL;
                    $url = $this->getConfig($path);
                    $url = $this->_updatePathUseRewrites($url);
                    $url = $this->_updatePathUseStoreView($url);
                    break;

                case UrlInterface::URL_TYPE_DIRECT_LINK:
                    $path = $secure ? self::XML_PATH_SECURE_BASE_LINK_URL : self::XML_PATH_UNSECURE_BASE_LINK_URL;
                    $url = $this->getConfig($path);
                    $url = $this->_updatePathUseRewrites($url);
                    break;

                case UrlInterface::URL_TYPE_STATIC:
                    $path = $secure ? self::XML_PATH_SECURE_BASE_STATIC_URL : self::XML_PATH_UNSECURE_BASE_STATIC_URL;
                    $url = $this->getConfig($path);
                    if (!$url) {
                        $url = $this->getBaseUrl(UrlInterface::URL_TYPE_WEB, $secure)
                            . $this->filesystem->getUri(DirectoryList::STATIC_VIEW);
                    }
                    break;

                case UrlInterface::URL_TYPE_MEDIA:
                    $url = $this->_getMediaScriptUrl($this->filesystem, $secure);
                    if (!$url) {
                        $path = $secure ? self::XML_PATH_SECURE_BASE_MEDIA_URL : self::XML_PATH_UNSECURE_BASE_MEDIA_URL;
                        $url = $this->getConfig($path);
                        if (!$url) {
                            $url = $this->getBaseUrl(UrlInterface::URL_TYPE_WEB, $secure)
                                . $this->filesystem->getUri(DirectoryList::MEDIA);
                        }
                    }
                    break;

                default:
                    throw new \InvalidArgumentException('Invalid base url type');
            }

            if (false !== strpos($url, self::BASE_URL_PLACEHOLDER)) {
                $url = str_replace(self::BASE_URL_PLACEHOLDER, $this->_request->getDistroBaseUrl(), $url);
            }

            $this->_baseUrlCache[$cacheKey] = rtrim($url, '/') . '/';
        }

        return $this->_baseUrlCache[$cacheKey];
    }
    protected function _updateUrlUseVendor($url)
    {
        $baseCheck = !$this->_curMySecure || $this->_udSkipBaseCheck;
        if ($baseCheck && $this->useVendorUrl() !== false || $this->useVendorUrl()===true) {
            $msHlp = ObjectManager::getInstance()->get('\Unirgy\DropshipMicrosite\Helper\Data');
            if ($this->useVendorUrl() === true
                || $this->useVendorUrl() === null && $msHlp->getCurUpdateStoreBaseUrl()
            ) {
                $vendor = $msHlp->getCurrentVendor();
            } else {
                $vendor = $this->useVendorUrl();
            }
            if ($vendor
                && ($vendor = ObjectManager::getInstance()->get('\Unirgy\Dropship\Helper\Data')->getVendor($vendor))
                && $vendor->getId()
            ) {
                if (1 == $msHlp->getCurSubdomainLevel($vendor)) {
                    return $this->_updatePathUseVendorKey($url, $vendor);
                } elseif (!$msHlp->getUpdateStoreBaseUrl($vendor)
                    || !$msHlp->getCurrentVendor()
                    || !$msHlp->getCurrentVendor()->getId()==$vendor->getId()
                ) {
                    $url = $msHlp->getVendorBaseUrl($vendor);
                    return parent::_updatePathUseStoreView($url);
                }
            }
        }
        return $url;
    }
    protected function _updatePathUseStoreView($url)
    {
        $url = $this->_updateUrlUseVendor($url);
        return parent::_updatePathUseStoreView($url);
    }
    protected function _updatePathUseVendorKey($url, $vendor)
    {
        $storeBeforeVendor = $this->_config->isSetFlag('udropship/microsite/url_store_before_vendor', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->getId());
        if ($storeBeforeVendor) {
            $url = parent::_updatePathUseStoreView($url);
            $url .= $vendor->getUrlKey().'/';
        } else {
            $url .= $vendor->getUrlKey().'/';
            $url = parent::_updatePathUseStoreView($url);
        }
        return $url;
    }
    public function getCurrentUrl($fromStore = true)
    {
        $this->udSkipBaseCheck(true);
        $this->useVendorUrl(true);
        $url = parent::getCurrentUrl($fromStore);
        $this->resetUseVendorUrl();
        $this->udSkipBaseCheck(false);
        return $url;
    }
    protected $_udSkipBaseCheck=false;
    public function udSkipBaseCheck($flag)
    {
        $this->_udSkipBaseCheck = $flag;
        return $this;
    }
}