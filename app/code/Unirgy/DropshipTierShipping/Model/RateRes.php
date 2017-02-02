<?php

namespace Unirgy\DropshipTierShipping\Model;

use Magento\Framework\DataObject;

/**
 * Class RateRes
 * @package Unirgy\DropshipTierShipping\Model
 */
class RateRes extends DataObject
{
    /**
     * @var string
     */
    protected $_specPrefix = '__specific_';

    /**
     * @return string
     */
    public function specPrefix()
    {
        return $this->_specPrefix;
    }

    /**
     * @param $subkey
     * @return bool
     */
    public function isProductRate($subkey)
    {
        return $this->getData($this->_specPrefix . $subkey . '/is_product')
        || $this->getData($this->_specPrefix . $subkey . '/is_udmulti');
    }

    /**
     * @param $subkey
     * @return mixed
     */
    public function isFallbackRate($subkey)
    {
        return $this->getData($this->_specPrefix . $subkey . '/is_fallback');
    }

    /**
     * @param $subkey
     * @return mixed
     */
    public function isVendorRate($subkey)
    {
        return $this->getData($this->_specPrefix . $subkey . '/is_vendor');
    }

    /**
     * @param $subkey
     * @return mixed
     */
    public function isGlobalRate($subkey)
    {
        return $this->getData($this->_specPrefix . $subkey . '/is_global');
    }

    /**
     * @param $subkey
     * @return bool
     */
    public function isCategoryRate($subkey)
    {
        return !$this->isFallbackRate($subkey) && !$this->isProductRate($subkey);
    }
}
