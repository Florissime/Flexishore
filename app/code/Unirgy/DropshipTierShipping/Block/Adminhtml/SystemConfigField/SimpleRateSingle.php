<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

/**
 * Class SimpleRateSingle
 * @package Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField
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
            $this->setTemplate('Unirgy_DropshipTierShipping::udtiership/system/form_field/simple_rate_single.phtml');
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
        switch ($skType) {
            case 'cost':
            case 'additional':
                $suffix = (string)$store->getBaseCurrencyCode();
                break;
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
