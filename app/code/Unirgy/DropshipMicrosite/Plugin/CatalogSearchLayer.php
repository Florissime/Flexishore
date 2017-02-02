<?php

namespace Unirgy\DropshipMicrosite\Plugin;

class CatalogSearchLayer
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
    public function beforePrepareProductCollection(\Magento\Catalog\Model\Layer\Search $subject, $collection)
    {
        $this->_msHlp->addVendorFilterToProductCollection($collection);
    }
}