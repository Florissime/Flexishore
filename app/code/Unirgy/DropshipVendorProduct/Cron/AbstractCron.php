<?php

namespace Unirgy\DropshipVendorProduct\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;
use Magento\Catalog\Model\ProductFactory;

class AbstractCron
{
    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    protected $_storeManager;
    protected $_prodHlp;

    public function __construct(
        \Unirgy\DropshipVendorProduct\Helper\Data $vendorProductHelper,
        DropshipHelperData $udropshipHelper,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logLoggerInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ProductFactory $productFactory
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_prodHlp = $vendorProductHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_logger = $logLoggerInterface;
        $this->_productFactory = $productFactory;
    }
    public function prepareForNotification($prods)
    {
        foreach ($prods as $prod) {
            foreach ([
                 'udprod_attributes_changed',
                 'udprod_cfg_simples_added',
                 'udprod_cfg_simples_removed'] as $descrAttr
            ) {
                $descr = $prod->getData($descrAttr);
                if (!is_array($descr)) {
                    try {
                        $descr = unserialize($descr);
                    } catch (\Exception $e) {
                        $descr = [];
                    }
                }
                if (!is_array($descr)) {
                    $descr = [];
                }
                $prod->setData($descrAttr, $descr);
            }
        }
    }
}