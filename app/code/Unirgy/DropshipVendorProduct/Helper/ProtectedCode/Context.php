<?php

namespace Unirgy\DropshipVendorProduct\Helper\ProtectedCode;

use Unirgy\DropshipVendorProduct\Helper\Data as HelperData;
use Unirgy\DropshipVendorProduct\Model\Source;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;
use Unirgy\Dropship\Helper\ProtectedCode as HelperProtectedCode;

class Context
{
    /**
     * @var HelperData
     */
    public $_prodHlp;

    /**
     * @var DropshipHelperData
     */
    public $_hlp;

    /**
     * @var Source
     */
    public $_prodSrc;

    public function __construct(
        \Unirgy\DropshipVendorProduct\Helper\Data $helperData,
        \Unirgy\Dropship\Helper\Data $dropshipHelperData,
        \Unirgy\DropshipVendorProduct\Model\Source $modelSource
    )
    {
        $this->_prodHlp = $helperData;
        $this->_hlp = $dropshipHelperData;
        $this->_prodSrc = $modelSource;

    }
}