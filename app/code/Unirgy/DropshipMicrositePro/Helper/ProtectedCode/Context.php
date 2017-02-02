<?php

namespace Unirgy\DropshipMicrositePro\Helper\ProtectedCode;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Helper\ProtectedCode as HelperProtectedCode;

class Context
{
    /**
     * @var HelperData
     */
    public $_hlp;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $helperData,
        \Magento\Framework\App\Config\ScopeConfigInterface $configScopeConfigInterface
    )
    {
        $this->_hlp = $helperData;
        $this->scopeConfig = $configScopeConfigInterface;
    }
}