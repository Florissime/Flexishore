<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\Renderer;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

/**
 * Class Rates
 * @package Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\Renderer
 */
class Rates extends Widget implements RendererInterface
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var null
     */
    protected $_element = null;

    /**
     * Rates constructor.
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
        $this->setTemplate('Unirgy_DropshipTierShipping::udtiership/vendor/helper/category_rates_config.phtml');
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->toHtml();
    }

    /**
     * @param AbstractElement $element
     * @return $this
     */
    public function setElement(AbstractElement $element)
    {
        $this->_element = $element;
        return $this;
    }

    /**
     * @return null
     */
    public function getElement()
    {
        return $this->_element;
    }

    /**
     * @return \Magento\Framework\Data\Collection
     */
    public function getTopCategories()
    {
        return $this->_helperData->getTopCategories();
    }

    /**
     * @return array
     */
    public function getTiershipRates()
    {
        $value = $this->_element->getValue();
        if (is_string($value)) {
            $value = unserialize($value);
        }
        if (!is_array($value)) {
            $value = [];
        }
        return $value;
    }

    /**
     * @return mixed
     */
    public function getGlobalTierShipConfig()
    {
        $value = $this->_scopeConfig->getValue('carriers/udtiership/rates', ScopeInterface::SCOPE_STORE);
        if (is_string($value)) {
            $value = unserialize($value);
        }
        return $value;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface|null
     */
    public function getStore()
    {
        return $this->_storeManager->getDefaultStoreView();
    }

    /**
     * @param $subkeyColumns
     * @param $idx
     * @return string
     */
    public function getColumnTitle($subkeyColumns, $idx)
    {
        reset($subkeyColumns);
        $i = 0;
        while ($i++ != $idx) next($subkeyColumns);
        $title = '';
        $column = current($subkeyColumns);
        switch ($column[1]) {
            case 'cost':
                $title = __('Cost for the first item');
                break;
            case 'additional':
                $title = __('Additional item cost');
                break;
            case 'handling':
                $title = __('Tier handling fee');
                break;
        }
        return $title;
    }

    /**
     * @return bool
     */
    public function isShowAdditionalColumn()
    {
        return $this->_helperData->useAdditional($this->getStore());
    }

    /**
     * @return bool
     */
    public function isShowHandlingColumn()
    {
        return $this->_helperData->useHandling($this->getStore());
    }
}
