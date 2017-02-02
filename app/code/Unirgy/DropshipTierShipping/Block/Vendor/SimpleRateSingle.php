<?php

namespace Unirgy\DropshipTierShipping\Block\Vendor;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\Store;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

/**
 * Class SimpleRateSingle
 * @package Unirgy\DropshipTierShipping\Block\Vendor
 */
class SimpleRateSingle extends Template
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * SimpleRateSingle constructor.
     * @param Context $context
     * @param HelperData $helperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        HelperData $helperData,
        array $data = []
    ) {
        $this->_helperData = $helperData;

        parent::__construct($context, $data);
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipTierShipping::unirgy/tiership/vendor/simple_rate_single.phtml');
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return sprintf('%s[%s][%s]',
                       $this->getBaseName(), $this->getKey(), $this->getSubkey()
        );
    }

    /**
     * @return string
     */
    public function getValue()
    {
        if ($this->hasMaxKey()) {
            $k = [$this->getDataObject(), $this->getKey()];
        } else {
            $k = [$this->getGlobalDataObject(), $this->getGlobKey()];
        }
        $value = $k[0]->getData($k[1] . '/' . $this->getSubkey(), false);
        return (string)$value;
    }

    /**
     * @param $key
     * @param bool $globKey
     * @return $this
     */
    public function initKey($key, $globKey = false)
    {
        $this->setKey($key);
        if ($globKey === false) $globKey = $key;
        $this->setGlobKey($globKey);
        return $this;
    }

    /**
     * @var array
     */
    protected $_subkeyDef = ['align', 'subkey_type', 'subkey', 'max_key'];

    /**
     * @param $skType
     * @return $this
     */
    public function initSubkey($skType)
    {
        $skTypeCnt = count($skType);
        while ($skTypeCnt++ < count($this->_subkeyDef)) {
            $skType[] = false;
        }
        $sk = array_combine($this->_subkeyDef, $skType);
        $this->addData($sk);
        return $this;
    }

    /**
     * @return bool
     */
    public function hasMaxKey()
    {
        return $this->getData('max_key') !== false;
    }

    /**
     * @return bool|string
     */
    public function getMaxValue()
    {
        if (!$this->hasMaxKey()) return false;
        return (string)$this->getGlobalDataObject()->getData(sprintf('%s/%s',
                                                                     $this->getKey(), $this->getData('max_key')
                                                             ));
    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        $tsHlp = $this->_helperData;
        $suffix = '';
        $skType = $this->getSubkeyType();
        /** @var Store $store */
        $store = $this->getStore();
        switch ($skType) {
            case 'cost':
            case 'additional':
                $suffix = (string)$store->getBaseCurrencyCode();
                break;
        }
        return $suffix;
    }

    /**
     * @return string
     */
    public function formatedValue()
    {
        $format = '%s';
        $formatted = '';
        $skType = $this->getSubkeyType();
        /** @var Store $store */
        $store = $this->getStore();
        if ($this->getValue() === null || $this->getValue() === '') {
            return '';
        }
        switch ($skType) {
            case 'cost':
            case 'additional':
                $formatted = $store->getCurrentCurrency()->format($this->getValue());
                break;
        }
        return !$formatted && $this->getValue() !== null && $this->getValue() !== ''
            ? sprintf($format, $this->getValue())
            : $formatted;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface|null
     */
    public function getStore()
    {
        return $this->_storeManager->getDefaultStoreView();
    }
}
