<?php

namespace Unirgy\Dropship\Plugin;

class AppCron extends AppAbstract
{
    public function beforeLaunch(\Magento\Framework\App\Cron $subject)
    {
        $this->initConfigRewrite();
    }
}