<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form;

use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Text;
use Magento\Framework\Escaper;
use Magento\Framework\View\Layout;
use Unirgy\Dropship\Helper\Catalog;

class TierPrice extends Text
{
    /**
     * @var Catalog
     */
    protected $_helperCatalog;

    /**
     * @var Layout
     */
    protected $_viewLayout;

    public function __construct(
        Catalog $helperCatalog,
        Layout $viewLayout,
        Factory $factoryElement,
        CollectionFactory $factoryCollection, 
        Escaper $escaper,
        $data = []
    ) {
        $this->_helperCatalog = $helperCatalog;
        $this->_viewLayout = $viewLayout;

        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    public function getElementHtml()
    {
        //$this->_helperCatalog->setDesignStore();
        $html = $this->_viewLayout->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\TierPrice')->render($this);
        //$this->_helperCatalog->setDesignStore(0, 'adminhtml');
        return $html;
    }
}