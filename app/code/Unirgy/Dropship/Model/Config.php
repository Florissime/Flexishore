<?php

namespace Unirgy\Dropship\Model;

class Config
{
    /**
     * @var \Magento\Framework\App\Route\Config\Reader
     */
    protected $_reader;

    /**
     * @var \Magento\Framework\Cache\FrontendInterface
     */
    protected $_cache;

    /**
     * @var string
     */
    protected $_cacheId;

    /**
     * @var \Magento\Framework\Config\ScopeInterface
     */
    protected $_configScope;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_data;

    /**
     * @param Config\Reader $reader
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param \Magento\Framework\Config\ScopeInterface $configScope
     * @param string $cacheId
     */
    public function __construct(
        Config\Reader $reader,
        \Magento\Framework\Config\CacheInterface $cache,
        \Magento\Framework\Config\ScopeInterface $configScope,
        $cacheId = 'UdropshipConfig'
    ) {
        $this->_reader = $reader;
        $this->_cache = $cache;
        $this->_cacheId = $cacheId;
        $this->_configScope = $configScope;
    }

    protected function _loadData()
    {
        if ($this->_data===null) {
            $cacheId = $this->_cacheId;
            $cachedData = unserialize($this->_cache->load($cacheId));
            if (is_array($cachedData)) {
                $this->_initFromSource($cachedData);
                return $this;
            }
            $source = $this->_reader->read();
            $this->_initFromSource($source);
            $this->_cache->save(serialize($source), $cacheId);
        }
        return $this;
    }

    protected function _initFromSource($source)
    {
        $this->_translate = $source['translate'];
        $output = [];
        foreach ($source['output'] as $key => $value) {
            $this->_setArrayValue($output, $key, $value);
        }
        $this->_data = new \Magento\Framework\DataObject($output);
        return $this;
    }

    protected function _setArrayValue(array &$container, $path, $value)
    {
        $segments = explode('/', $path);
        $currentPointer = & $container;
        foreach ($segments as $segment) {
            if (!isset($currentPointer[$segment])) {
                $currentPointer[$segment] = [];
            }
            $currentPointer = & $currentPointer[$segment];
        }
        $currentPointer = $value;
    }

    public function setConfigData($path, $value)
    {
        $this->_data->setData($path, $value);
        return $this;
    }

    public function getFieldset($name=null, $field=null)
    {
        return $this->_getData('vendor/fieldsets', $name, $field);
    }
    public function getField($name=null, $field=null)
    {
        return $this->_getData('vendor/fields', $name, $field);
    }
    public function getTrackApi($name=null, $field=null)
    {
        return $this->_getData('track_api', $name, $field);
    }
    public function getLabel($name=null, $field=null)
    {
        return $this->_getData('labels', $name, $field);
    }
    public function getLabelType($name=null, $field=null)
    {
        return $this->_getData('label_types', $name, $field);
    }
    public function getAvailabilityMethod($name=null, $field=null)
    {
        return $this->_getData('availability_methods', $name, $field);
    }
    public function getStockcheckMethod($name=null, $field=null)
    {
        return $this->_getData('stockcheck_methods', $name, $field);
    }
    public function getBatchAdapter($type, $name=null, $field=null)
    {
        return $this->_getData('batch_adapters', $type.($name ? '/'.$name : ''), $field);
    }
    public function getNotificationMethod($name=null, $field=null)
    {
        return $this->_getData('notification_methods', $name, $field);
    }
    public function getProductState($name=null, $field=null)
    {
        return $this->_getData('product_state', $name, $field);
    }
    public function getPayoutMethod($name=null, $field=null)
    {
        return $this->_getData('payout/method', $name, $field);
    }
    protected function _getData($key, $name=null, $field=null)
    {
        $this->_loadData();
        if ($name===null) {
            $result = (array)$this->_data->getData($key);
        } else {
            $key .= '/'.$name;
            if ($field!==null) {
                $key .= '/'.$field;
            }
            $result = $this->_data->getData($key);
        }
        return $result;
    }
}