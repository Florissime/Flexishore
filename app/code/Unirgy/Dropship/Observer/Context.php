<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    \Unirgy\Dropship
 * @copyright  Copyright (c) 2015-2016 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\Dropship\Observer;

use \Magento\Eav\Model\Config;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\DataObject;
use \Magento\Framework\Event\ManagerInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Helper\Error;
use \Unirgy\Dropship\Model\Source;

class Context
{
    /**
     * @var HelperData
     */
    public $hlp;

    /**
     * @var Resource
     */
    public $rHlp;

    /**
     * @var Config
     */
    public $eavConfig;

    /**
     * @var Error
     */
    public $helperError;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var ManagerInterface
     */
    public $eventManager;

    public $iHlp;

    public function __construct(
        \Unirgy\Dropship\Helper\Item $itemHelper,
        \Unirgy\Dropship\Helper\Data $helperData,
        \Unirgy\Dropship\Model\ResourceModel\Helper $resourceHelper,
        Config $eavConfig,
        Error $helperError,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ManagerInterface $eventManager,
        array $data = []
    )
    {
        $this->iHlp = $itemHelper;
        $this->hlp = $helperData;
        $this->rHlp = $resourceHelper;
        $this->eavConfig = $eavConfig;
        $this->helperError = $helperError;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;
    }
}