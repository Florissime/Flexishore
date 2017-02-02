<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\View\Element\Template;

class Fieldset extends Template implements RendererInterface
{
    protected $_element;

    protected function _construct()
    {
        $this->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/vendor/product/renderer/fieldset.phtml');
    }

    public function getElement()
    {
        return $this->_element;
    }

    public function render(AbstractElement $element)
    {
        $this->_element = $element;
        return $this->toHtml();
    }
    public function getChildElementHtml($elem)
    {
        return $this->getElement()->getForm()->getElement($elem)->toHtml();
    }
    public function getChildElement($elem)
    {
        return $this->getElement()->getForm()->getElement($elem);
    }
    public function isHidden($elem)
    {
        return $this->getElement()->getForm()->getElement($elem)->getIsHidden();
    }
}