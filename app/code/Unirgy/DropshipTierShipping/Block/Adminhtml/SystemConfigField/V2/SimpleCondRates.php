<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField\V2;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Layout;

/**
 * Class SimpleCondRates
 * @package Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField\V2
 */
class SimpleCondRates extends Field
{
    /**
     * @var Layout
     */
    protected $_viewLayout;

    /**
     * @var null
     */
    protected $_element = null;

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _construct()
    {
        parent::_construct();
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipTierShipping::udtiership/system/form_field/v2/simple_cond_rates.phtml');
        }
        if (($head = $this->getLayout()->getBlock('head'))) {
            $head->setCanLoadExtJs(true);
        }
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
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSubrowsContainerBlock($fieldName)
    {
        return $this->getLayout()->getBlockSingleton('Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField\V2\SimpleCondRates\Subrows')
            ->setTemplate('Unirgy_DropshipTierShipping::udtiership/system/form_field/v2/simple_cond_rates/subrows.phtml')
            ->setFieldName($fieldName);
    }

}
