<?php

namespace Unirgy\DropshipMicrositePro\Block\Vendor\Register\Renderer;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Element\Template;

class FieldsetElementJs extends Template
{
    protected $_element;

    protected function _construct()
    {
        $this->setTemplate('Unirgy_DropshipMicrositePro::unirgy/udmspro/vendor/register/renderer/fieldset_element_js.phtml');
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