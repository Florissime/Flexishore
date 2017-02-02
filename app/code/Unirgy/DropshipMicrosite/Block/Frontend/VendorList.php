<?php

namespace Unirgy\DropshipMicrosite\Block\Frontend;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\LayoutFactory;
use Unirgy\Dropship\Model\VendorFactory;

class VendorList extends Template
{
    /**
     * @var VendorFactory
     */
    protected $_vendorFactory;

    public function __construct(
        Context $context,
        VendorFactory $modelVendorFactory,
        array $data = [])
    {
        $this->_vendorFactory = $modelVendorFactory;

        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        if ($toolbar = $this->getLayout()->getBlock('umicrosite_list.toolbar')) {
            $toolbar->setCollection($this->getVendorsCollection());
            $this->setChild('toolbar', $toolbar);
        }

        return $this;
    }
    protected $_vendorCollection;
    public function getVendorsCollection()
    {
        if (null === $this->_vendorCollection) {
            $this->_vendorCollection = $this->_vendorFactory->create()->getCollection()->addStatusFilter('A');
        }
        return $this->_vendorCollection;
    }
    public function getSize()
    {
        return $this->getVendorsCollection()->getSize();
    }

    public function getToolbarHtml()
    {
        return $this->getChildHtml('toolbar');
    }
}