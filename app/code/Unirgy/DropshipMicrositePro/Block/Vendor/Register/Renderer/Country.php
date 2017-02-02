<?php

namespace Unirgy\DropshipMicrositePro\Block\Vendor\Register\Renderer;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\View\Element\Template;

class Country extends Template implements RendererInterface
{
    protected $_element;

    protected function _construct()
    {
        $this->setTemplate('Unirgy_DropshipMicrositePro::unirgy/udmspro/vendor/register/renderer/country.phtml');
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

    public function getElementHtml()
    {
        $html = $this->_element->getElementHtml();
        return $html;
    }
}