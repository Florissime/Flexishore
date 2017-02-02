<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Renderer;

use Magento\Backend\Block\Widget;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * Class SimpleRates
 * @package Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Renderer
 */
class SimpleRates extends Widget implements RendererInterface
{
    /**
     * @var null
     */
    protected $_element = null;

    /**
     *
     */
    public function _construct()
    {
        parent::_construct();
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipTierShipping::udtiership/vendor/form/renderer/v2/simple_rates.phtml');
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
}
