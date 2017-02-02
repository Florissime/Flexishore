<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class UseConfigElement extends Template implements RendererInterface
{
    /**
     * @var Registry
     */
    protected $_frameworkRegistry;

    protected $_element;
    public function __construct(Context $context, 
        Registry $frameworkRegistry, 
        array $data = [])
    {
        $this->_frameworkRegistry = $frameworkRegistry;

        parent::__construct($context, $data);

        $this->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/vendor/product/renderer/use_config_select.phtml');
    }
    public function getProduct()
    {
        return $this->_frameworkRegistry->registry('product');
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

    public function getHtmlId($htmlId, $type=0)
    {
        $form = $this->_element->getForm();
        $elHtmlId = $this->_element->getData('html_id');
        if ($type===true) {
            $htmlId = $htmlId.$elHtmlId;
        } elseif ($type===false) {
            $htmlId = $elHtmlId.$htmlId;
        }
        return $form->getHtmlIdPrefix() . $htmlId . $form->getHtmlIdSuffix();
    }
    public function getName($name, $type=0)
    {
        $form = $this->_element->getForm();
        $elName = $this->_element->getData('name');
        if ($type===true) {
            $name = $name.$elName;
        } elseif ($type===false) {
            $name = $elName.$name;
        }
        if ($suffix = $form->getFieldNameSuffix()) {
            $name = $form->addSuffixToName($name, $suffix);
        }
        return $name;
    }
}
