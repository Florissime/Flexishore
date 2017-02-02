<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form;

use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Text;
use Magento\Framework\Escaper;
use Magento\Framework\View\Layout;
use Unirgy\Dropship\Helper\Catalog;

class Gallery extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    /**
     * @var Catalog
     */
    protected $_helperCatalog;

    /**
     * @var Layout
     */
    protected $_viewLayout;

    public function __construct(Factory $factoryElement,
                                CollectionFactory $factoryCollection,
                                Escaper $escaper,
                                Catalog $helperCatalog,
                                Layout $viewLayout)
    {
        $this->_helperCatalog = $helperCatalog;
        $this->_viewLayout = $viewLayout;

        parent::__construct($factoryElement, $factoryCollection, $escaper);
    }

    public function getElementHtml()
    {
        //$this->_helperCatalog->setDesignStore();
        $html = $this->_viewLayout
            ->createBlock('\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Gallery')
            ->setValue($this->getValue())
            ->toHtml();
        //$this->_helperCatalog->setDesignStore(0, 'adminhtml');
        return $html;
    }
}