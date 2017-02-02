<?php

namespace Unirgy\Dropship\Cron;

class VendorNotifyLowstock extends AbstractCron
{
    public function execute()
    {
        $this->_vendorNotifylowstock->vendorNotifyLowstock();
    }
}