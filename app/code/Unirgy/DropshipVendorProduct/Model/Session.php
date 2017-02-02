<?php

namespace Unirgy\DropshipVendorProduct\Model;

use Magento\Framework\Session\SessionManager;

class Session extends SessionManager
{
    public function __construct()
    {
        $this->init('udprod');
    }
}
