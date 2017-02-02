<?php

namespace Unirgy\DropshipMicrosite\Plugin;

class CatalogWidgetProductList
{
    /** @var \Unirgy\Dropship\Helper\Data */
    protected $_hlp;
    /** @var \Unirgy\DropshipMicrosite\Helper\Data */
    protected $_msHlp;
    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Unirgy\DropshipMicrosite\Helper\Data $micrositeHelper
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_msHlp = $micrositeHelper;
    }
    public function afterCreateCollection(\Magento\CatalogWidget\Block\Product\ProductsList $subject, $result)
    {
        $this->_msHlp->addVendorFilterToProductCollection($result);
        return $result;
    }
}