<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

/**
 * Class RateSingle
 * @package Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField
 */
class RateSingle extends Template
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * RateSingle constructor.
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
            $this->setTemplate('Unirgy_DropshipTierShipping::udtiership/system/form_field/rate_single.phtml');
        }
    }

    /**
     * @return string
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
        $dataObj = $this->getDataObject();
        $aKey = sprintf('%s/%s',
                        $this->getKey(), $this->getSubkey()
        );
        $value = $dataObj->getData($aKey, false);
        return (string)$value;
    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        $tsHlp = $this->_helperData;
        $suffix = '';
        $skType = $this->getSubkeyType();
        $store = $this->getStore();
        $isBase = $this->getIsBaseRate();
        switch ($skType) {
            case 'cost':
            case 'additional':
                if (!$isBase && $tsHlp->isCtPercentPerCustomerZone($skType, $store)) {
                    $suffix = '% to base';
                } elseif (!$isBase && $tsHlp->isCtFixedPerCustomerZone($skType, $store)) {
                    $suffix = 'fixed to base';
                } else {
                    $suffix = (string)$store->getBaseCurrencyCode();
                }
                break;
            case 'handling':
                if (!$tsHlp->isApplyMethodNone($skType, $store)) {
                    if (!$isBase && $tsHlp->isCtPercentPerCustomerZone($skType, $store)) {
                        $suffix = '% to base';
                    } elseif (!$isBase && $tsHlp->isCtFixedPerCustomerZone($skType, $store)) {
                        $suffix = 'fixed to base';
                    } else {
                        if ($tsHlp->isApplyMethodPercent($skType, $store)) {
                            $suffix = '%';
                        } else {
                            $suffix = (string)$store->getBaseCurrencyCode();
                        }
                    }
                }
                break;
        }
        return $suffix;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface|null
     */
    public function getStore()
    {
        return $this->_storeManager->getDefaultStoreView();
    }
}
