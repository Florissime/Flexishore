<?php

namespace Unirgy\DropshipMicrositePro\Block\Vendor\Register\Renderer;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\View\Element\Template;

class Fieldset extends Template implements RendererInterface
{
    protected $_element;

    protected function _construct()
    {
        $this->setTemplate('Unirgy_DropshipMicrositePro::unirgy/udmspro/vendor/register/renderer/fieldset.phtml');
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

    protected $_jsBlockForChild;
    public function getChildElementJs($elem)
    {
        if (null === $this->_jsBlockForChild) {
            $this->_jsBlockForChild = $this->getLayout()->createBlock('Unirgy\DropshipMicrositePro\Block\Vendor\Register\Renderer\FieldsetElementJs');
        }
        return $this->_jsBlockForChild->render($this->getElement()->getForm()->getElement($elem));
    }
}