<?php

namespace Unirgy\DropshipShippingClass\Block\Adminhtml\FormRenderer;

use Magento\Backend\Block\Widget;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class ShipclassRows extends Widget implements RendererInterface
{

    protected $_element = null;

    public function _construct()
    {
        $this->setTemplate('Unirgy_DropshipShippingClass::udshipclass/form_field/renderer/rows.phtml');
    }

    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->toHtml();
    }

    public function setElement(AbstractElement $element)
    {
        $this->_element = $element;
        return $this;
    }

    public function getElement()
    {
        return $this->_element;
    }

    public function suffixId($id)
    {
        return $id . $this->getElement()->getId();
    }

}
