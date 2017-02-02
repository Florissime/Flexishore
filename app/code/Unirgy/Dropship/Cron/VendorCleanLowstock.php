<?php

namespace Unirgy\Dropship\Cron;

class VendorCleanLowstock extends AbstractCron
{
    public function execute()
    {
        $this->_vendorNotifylowstock->vendorCleanLowstock();
    }
}