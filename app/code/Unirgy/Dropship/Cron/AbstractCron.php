<?php

namespace Unirgy\Dropship\Cron;

class AbstractCron
{
    /** @var \Unirgy\Dropship\Helper\Data */
    protected $_hlp;

    /** @var \Unirgy\Dropship\Helper\Error */
    protected $_errHlp;

    /** @var \Unirgy\Dropship\Model\Vendor\NotifyLowstock */
    protected $_vendorNotifylowstock;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Unirgy\Dropship\Helper\Error $errorHelper,
        \Unirgy\Dropship\Model\Vendor\NotifyLowstock $vendorNotifylowstock
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_errHlp = $errorHelper;
        $this->_vendorNotifylowstock = $vendorNotifylowstock;
    }
}