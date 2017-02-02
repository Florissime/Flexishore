<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Layout;
use Unirgy\DropshipVendorProduct\Helper\Data as HelperData;

class QuickCreateFieldset extends Template implements RendererInterface
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var Layout
     */
    protected $_viewLayout;

    public function __construct(Context $context, 
        HelperData $helperData, 
        Layout $viewLayout, 
        array $data = [])
    {
        $this->_helperData = $helperData;
        $this->_viewLayout = $viewLayout;

        parent::__construct($context, $data);
    }

    protected $_element;

    protected function _construct()
    {
        $this->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/vendor/product/renderer/qcfieldset.phtml');
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
    public function getChildElementHtml($elem='_cfg_quick_create')
    {
        return $this->getElement()->getForm()->getElement($elem)->toHtml();
    }
    public function getChildElement($elem='_cfg_quick_create')
    {
        return $this->getElement()->getForm()->getElement($elem);
    }
    protected $_product;
    public function setProduct($product)
    {
        $this->_product = $product;
        return $this;
    }
    public function getProduct()
    {
        return $this->_product;
    }
    public function getConfigurableAttributes()
    {
        return $this->_helperData->getConfigurableAttributes($this->getProduct(), !$this->getProduct()->getId());
    }
    public function getFirstAttribute()
    {
        $firstAttr = $this->_helperData->getCfgFirstAttribute($this->getProduct());
        if (!$firstAttr) {
            throw new \Exception('Options are not defined for this type of product');
        }
        return $firstAttr;
    }
    public function getFirstAttributes()
    {
        $firstAttr = $this->_helperData->getCfgFirstAttributes($this->getProduct());
        if (!$firstAttr) {
            throw new \Exception('Options are not defined for this type of product');
        }
        return $firstAttr;
    }
    public function getFirstAttributesValueTuples()
    {
        return $this->_helperData->getCfgFirstAttributesValueTuples($this->getProduct());
    }
    public function getFirstAttributeValues($used=null, $filters=[], $filterFlag=true)
    {
        return $this->getAttributeValues($this->getFirstAttribute(), $used, $filters, $filterFlag);
    }
    public function getAttributeValues($attribute, $used=null, $filters=[], $filterFlag=true)
    {
        return $this->_helperData->getCfgAttributeValues($this->getProduct(), $attribute, $used, $filters, $filterFlag);
    }

    public function renderQcPrices()
    {
        return '';
    }
}