<?php

namespace Unirgy\Dropship\Block\Adminhtml\StoreSwitcher\FormRenderer;

use \Magento\Backend\Block\Template;
use \Magento\Framework\Data\Form\Element\AbstractElement;
use \Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class Fieldset
    extends Template implements RendererInterface
{
    protected $_element;
    protected function _construct()
    {
        $this->setTemplate('Unirgy_Dropship::udropship/store_switcher/form_renderer/fieldset.phtml');
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

    public function getHintHtml()
    {
        return '';
    }
}
