<?php

namespace Unirgy\DropshipTierCommission\Observer;

use Unirgy\DropshipTierCommission\Helper\Data as HelperData;

abstract class AbstractObserver
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(HelperData $helper)
    {
        $this->_hlp = $helper;

    }
}
