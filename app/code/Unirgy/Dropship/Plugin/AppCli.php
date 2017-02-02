<?php

namespace Unirgy\Dropship\Plugin;

class AppCli extends AppAbstract
{
    public function beforeGetCommands(\Magento\Framework\Console\CommandList $subject)
    {
        $this->initConfigRewrite();
    }
}