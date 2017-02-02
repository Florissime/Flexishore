<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Options;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\View\Element\Template;
use Unirgy\Dropship\Helper\Catalog;

class CustomOptions extends Options implements RendererInterface
{
    /**
     * @var Catalog
     */
    protected $_helperCatalog;

    public function __construct(Context $context, 
        Catalog $helperCatalog, 
        array $data = [])
    {
        $this->_helperCatalog = $helperCatalog;

        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        $this->_helperCatalog->setDesignStore(0, 'adminhtml');
        $res = Template::_toHtml();
        $this->_helperCatalog->setDesignStore();
        return $res;
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
}