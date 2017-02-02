<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\View\LayoutInterface;

/**
 * Class SimpleRates
 * @package Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\Form
 */
class SimpleRates extends AbstractElement
{
    /**
     * @var LayoutInterface
     */
    protected $_layout;

    /**
     * SimpleRates constructor.
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param LayoutInterface $layout
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        LayoutInterface $layout
    ) {
        $this->_layout = $layout;

        parent::__construct($factoryElement, $factoryCollection, $escaper);
    }

    /**
     * @return mixed
     */
    public function getHtml()
    {
        $this->_renderer = $this->_layout->createBlock('Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\Renderer\SimpleRates');
        return parent::getHtml();
    }
}
