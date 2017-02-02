<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Unirgy\Dropship\Helper\Catalog;

class FieldsetElement extends Template implements RendererInterface
{
    /**
     * @var Catalog
     */
    protected $_helperCatalog;

    /**
     * @var Registry
     */
    protected $_frameworkRegistry;

    public function __construct(Context $context, 
        Catalog $helperCatalog, 
        Registry $frameworkRegistry, 
        array $data = [])
    {
        $this->_helperCatalog = $helperCatalog;
        $this->_frameworkRegistry = $frameworkRegistry;

        parent::__construct($context, $data);
    }

    protected $_element;

    protected function _construct()
    {
        $this->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/vendor/product/renderer/fieldset_element.phtml');
    }

    public function getElement()
    {
        return $this->_element;
    }

    public function render(AbstractElement $element)
    {
        $this->_element = $element;
        $element->addClass('udvalidate-'.$element->getId());
        return $this->toHtml();
    }

    public function getElementHtml()
    {
        //$this->_helperCatalog->setDesignStore(0, 'adminhtml');
        $html = $this->_element->getElementHtml();
        //$this->_helperCatalog->setDesignStore();
        return $html;
    }

    public function getProduct()
    {
        return $this->_frameworkRegistry->registry('current_product') ? $this->_frameworkRegistry->registry('current_product') : $this->_frameworkRegistry->registry('product');
    }

}