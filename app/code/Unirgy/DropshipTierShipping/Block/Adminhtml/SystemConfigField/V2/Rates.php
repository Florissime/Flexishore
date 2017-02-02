<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField\V2;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Layout;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

/**
 * Class Rates
 * @package Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField\V2
 */
class Rates extends Field
{

    /**
     * @var HelperData
     */
    protected $_helper;

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
        $this->_helper = $helperData;

        parent::__construct($context, $data);
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipTierShipping::udtiership/system/form_field/v2/rates.phtml');
        }
        if (($head = $this->getLayout()->getBlock('head'))) {
            $head->setCanLoadExtJs(true);
        }
    }

    /**
     * @return \Magento\Framework\Data\Collection
     */
    public function getTopCategories()
    {
        return $this->_helper->getTopCategories();
    }

    /**
     * @return bool
     */
    public function isShowAdditionalColumn()
    {
        return $this->_helper->isUseAdditional($this->getCalculationMethod());
    }

    /**
     * @return bool
     */
    public function isShowHandlingColumn()
    {
        return $this->_helper->isUseHandling($this->getHandlingApply());
    }

    /**
     * @return mixed
     */
    public function isCtCostBasePlusZone()
    {
        return $this->_helper->isCtBasePlusZone($this->getCtCost());
    }

    /**
     * @return mixed
     */
    public function isCtAdditionalBasePlusZone()
    {
        return $this->_helper->isCtBasePlusZone($this->getCtAdditional());
    }

    /**
     * @return mixed
     */
    public function isCtHandlingBasePlusZone()
    {
        return $this->_helper->isCtBasePlusZone($this->getCtHandling());
    }

    /**
     * @return mixed
     */
    public function getCtCost()
    {
        return $this->hasData('ct_cost')
            ? $this->getData('ct_cost')
            : $this->_scopeConfig->getValue('carriers/udtiership/cost_calculation_type',
                                            ScopeInterface::SCOPE_STORE, $this->getStore());
    }

    /**
     * @return mixed
     */
    public function getCtAdditional()
    {
        return $this->hasData('ct_additional')
            ? $this->getData('ct_additional')
            : $this->_scopeConfig->getValue('carriers/udtiership/additional_calculation_type',
                                            ScopeInterface::SCOPE_STORE, $this->getStore());
    }

    /**
     * @return mixed
     */
    public function getCtHandling()
    {
        return $this->hasData('ct_handling')
            ? $this->getData('ct_handling')
            : $this->_scopeConfig->getValue('carriers/udtiership/handling_calculation_type',
                                            ScopeInterface::SCOPE_STORE, $this->getStore());
    }

    /**
     * @return mixed
     */
    public function getHandlingApply()
    {
        return $this->hasData('handling_apply')
            ? $this->getData('handling_apply')
            : $this->_scopeConfig->getValue('carriers/udtiership/handling_apply_method',
                                            ScopeInterface::SCOPE_STORE, $this->getStore());
    }

    /**
     * @return mixed
     */
    public function getCalculationMethod()
    {
        return $this->hasData('calculation_method')
            ? $this->getData('calculation_method')
            : $this->_scopeConfig->getValue('carriers/udtiership/calculation_method',
                                            ScopeInterface::SCOPE_STORE, $this->getStore());
    }

    /**
     * @return array
     */
    public function getKeysForSubrows()
    {
        $res = [];
        if ($this->isCtCostBasePlusZone()) {
            $res[] = 'cost_extra';
        }
        if ($this->isShowAdditionalColumn() && $this->isCtAdditionalBasePlusZone()) {
            $res[] = 'additional_extra';
        }
        if ($this->isShowHandlingColumn() && $this->isCtHandlingBasePlusZone()) {
            $res[] = 'handling_extra';
        }
        return $res;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface|null
     */
    public function getStore()
    {
        return $this->_storeManager->getDefaultStoreView();
    }

    /**
     * @param $skType
     * @param $isBase
     * @return string
     */
    public function getSuffix($skType, $isBase)
    {
        $tsHlp = $this->_helper;
        $suffix = '';
        $store = $this->getStore();
        switch ($skType) {
            case 'cost':
                if (!$isBase && $tsHlp->isUseCtPercentPerCustomerZone($this->getCtCost())) {
                    $suffix = '% to base';
                } elseif (!$isBase && $tsHlp->isUseCtFixedPerCustomerZone($this->getCtCost())) {
                    $suffix = 'fixed to base';
                } else {
                    $suffix = (string)$store->getBaseCurrencyCode();
                }
                break;
            case 'additional':
                if (!$isBase && $tsHlp->isUseCtPercentPerCustomerZone($this->getCtAdditional())) {
                    $suffix = '% to base';
                } elseif (!$isBase && $tsHlp->isUseCtFixedPerCustomerZone($this->getCtAdditional())) {
                    $suffix = 'fixed to base';
                } else {
                    $suffix = (string)$store->getBaseCurrencyCode();
                }
                break;
            case 'handling':
                if (!$tsHlp->isNoneValue($this->getHandlingApply())) {
                    if (!$isBase && $tsHlp->isUseCtPercentPerCustomerZone($this->getCtHandling())) {
                        $suffix = '% to base';
                    } elseif (!$isBase && $tsHlp->isUseCtFixedPerCustomerZone($this->getCtHandling())) {
                        $suffix = 'fixed to base';
                    } else {
                        if ($tsHlp->isPercentValue($this->getHandlingApply())) {
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
     * @param AbstractElement $element
     * @return string
     */
    public function getElementHtml(AbstractElement $element)
    {
        return $this->_getElementHtml($element);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        if (!$this->getDeliveryType()) {
            $html = '<div id="' . $element->getHtmlId() . '_container"></div>';
        } else {
            $html = $this->_toHtml();
        }
        return $html;
    }

    /**
     * @return mixed|string
     */
    public function getFieldName()
    {
        return $this->getData('field_name')
            ? $this->getData('field_name')
            : ($this->getElement() ? $this->getElement()->getName() : '');
    }

    /**
     * @var
     */
    protected $_idSuffix;

    /**
     * @return $this
     */
    public function resetIdSuffix()
    {
        $this->_idSuffix = null;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdSuffix()
    {
        if (null === $this->_idSuffix) {
            $this->_idSuffix = $this->prepareIdSuffix($this->getFieldName());
        }
        return $this->_idSuffix;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function prepareIdSuffix($id)
    {
        return preg_replace('/[^a-zA-Z0-9\$]/', '_', $id);
    }

    /**
     * @param $id
     * @return string
     */
    public function suffixId($id)
    {
        return $id . $this->getIdSuffix();
    }

    /**
     * @return string
     */
    public function getAddButtonId()
    {
        return $this->suffixId('addBtn');
    }

    /**
     * @param $fieldName
     * @param $skType
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSubrowsContainerBlock($fieldName, $skType)
    {
        return $this->getLayout()->getBlockSingleton('Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField\V2\Rates\Subrows')
            ->setTemplate('Unirgy_DropshipTierShipping::udtiership/system/form_field/v2/rates/subrows.phtml')
            ->setFieldName($fieldName)
            ->setSuffix($this->getSuffix($skType, false))
            ->setParentBlock($this);
    }

}
