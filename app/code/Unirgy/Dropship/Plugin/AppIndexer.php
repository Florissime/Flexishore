<?php

namespace Unirgy\Dropship\Plugin;

class AppIndexer extends AppAbstract
{
    public function beforeLaunch(\Magento\Indexer\App\Indexer $subject)
    {
        $this->initConfigRewrite();
    }
}