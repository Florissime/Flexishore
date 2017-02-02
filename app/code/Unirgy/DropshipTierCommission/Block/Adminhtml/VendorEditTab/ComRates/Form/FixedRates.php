<?php

namespace Unirgy\DropshipTierCommission\Block\Adminhtml\VendorEditTab\ComRates\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\View\LayoutInterface;

class FixedRates extends AbstractElement
{
    /**
     * @var LayoutInterface
     */
    protected $_layout;

    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        LayoutInterface $layout,
        $data = []
    ) {
        $this->_layout = $layout;

        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    public function getHtml()
    {
        $this->_renderer = $this->_layout->createBlock('Unirgy\DropshipTierCommission\Block\Adminhtml\VendorEditTab\ComRates\Renderer\FixedRates');
        return parent::getHtml();
    }
}
