<?php

namespace Unirgy\Dropship\Model\Vendor\Decision;

use \Magento\Catalog\Model\Product;
use \Magento\Framework\DataObject;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Helper\Item;
use \Unirgy\Dropship\Model\Stock\Availability;

class AbstractDecision extends DataObject
{
    /**
     * @var Item
     */
    protected $_iHlp;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var Availability
     */
    protected $_stockAvailability;

    public function __construct(
        Item $itemHelper,
        HelperData $udropshipHelper,
        Availability $stockAvailability,
        array $data = []
    )
    {
        $this->_iHlp = $itemHelper;
        $this->_hlp = $udropshipHelper;
        $this->_stockAvailability = $stockAvailability;

        parent::__construct($data);
    }

    public function apply($items)
    {
        $iHlp = $this->_iHlp;
        $localVendorId = $this->_hlp->getLocalVendorId();
        foreach ($items as $item) {
            $vendorId = $localVendorId;
            $product = $item->getProduct();
            if (!$item->getProduct()) {
                if (!$item->getProductId()) {
                    $iHlp->setUdropshipVendor($item, $localVendorId);
                    continue;
                }
                $item->setProduct($this->_hlp->createObj('\Magento\Catalog\Model\Product')->load($item->getProductId()));
            }
            $product = $item->getProduct();
            if ($product->getUdropshipVendor()) {
                $vendorId = $product->getUdropshipVendor();
            }
            $iHlp->setUdropshipVendor($item, $vendorId);
        }
        return $this;
    }

    public function collectStockLevels($items, $options=array())
    {
        $this->_stockAvailability->collectStockLevels($items, $options);
        return $this;
    }


}