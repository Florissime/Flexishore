<?php

namespace Unirgy\DropshipTierShipping\Block\Vendor\V2;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Layout;

/**
 * Class SimpleCondRates
 * @package Unirgy\DropshipTierShipping\Block\Vendor\V2
 */
class SimpleCondRates extends Template implements RendererInterface
{
    /**
     * @var null
     */
    protected $_element = null;

    /**
     * @inheritdoc
     */
    public function _construct()
    {
        parent::_construct();
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipTierShipping::unirgy/tiership/vendor/v2/simple_cond_rates.phtml');
        }
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        if (!$element->getDeliveryType()) {
            $html = '<div id="' . $element->getHtmlId() . '_container"></div>';
        } else {
            $html = $this->toHtml();
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
        return $this->getLayout()->getBlockSingleton('Unirgy\DropshipTierShipping\Block\Vendor\V2\SimpleCondRates\Subrows')
            ->setTemplate('Unirgy_DropshipTierShipping::unirgy/tiership/vendor/v2/simple_cond_rates/subrows.phtml')
            ->setFieldName($fieldName);
    }
}
