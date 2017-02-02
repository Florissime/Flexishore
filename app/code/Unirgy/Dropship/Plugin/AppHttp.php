<?php

namespace Unirgy\Dropship\Plugin;

class AppHttp extends AppAbstract
{
    public function beforeLaunch(\Magento\Framework\App\Http $subject)
    {
        $this->initConfigRewrite();
    }
}