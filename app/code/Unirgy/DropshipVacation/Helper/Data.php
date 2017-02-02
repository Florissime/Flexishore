<?php

namespace Unirgy\DropshipVacation\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Unirgy\DropshipVacation\Model\Source;
use Unirgy\DropshipVendorProduct\Model\ProductStatus;
use Unirgy\Dropship\Helper\Data as HelperData;

class Data extends AbstractHelper
{
    /**
     * @var Config
     */
    protected $_eavConfig;

    /**
     * @var HelperData
     */
    protected $_hlp;

    protected $_catalogHelper;

    public function __construct(
        Config $modelConfig,
        HelperData $helperData,
        \Unirgy\Dropship\Helper\Catalog $catalogHelper,
        Context $context
    )
    {
        $this->_eavConfig = $modelConfig;
        $this->_hlp = $helperData;
        $this->_catalogHelper = $catalogHelper;

        parent::__construct($context);
    }

    public function processVendorChange($vendor)
    {
        $write = $this->_hlp->rHlp()->getConnection();
        $disabled = Source::MODE_VACATION_DISABLE;
        if ($vendor->dataHasChangedFor('vacation_mode')) {
            if ($vendor->getData('vacation_mode') == $disabled) {
                $prodStatus = ProductStatus::STATUS_VACATION;
                $fromStatus = Status::STATUS_ENABLED;
            } elseif ($vendor->getOrigData('vacation_mode') == $disabled) {
                $prodStatus = Status::STATUS_ENABLED;
                $fromStatus = ProductStatus::STATUS_VACATION;
            }
            if (isset($prodStatus) && ($assocPids = $vendor->getResource()->getVendorAttributeProducts($vendor))) {
                $assocPids = array_keys($assocPids);
                $pStAttr = $this->_eavConfig->getAttribute('catalog_product', 'status');
                $pStUpdateSql = sprintf('update %s set value=%s where %s in (%s) and attribute_id=%s and value=%s',
                    $pStAttr->getBackendTable(), $write->quote($prodStatus), $this->_hlp->rowIdField(),
                    $write->quote($assocPids), $write->quote($pStAttr->getAttributeId()),
                    $write->quote($fromStatus)
                );
                $write->query($pStUpdateSql);
                $this->_catalogHelper->reindexPids($assocPids);
            }
        }
    }
}
