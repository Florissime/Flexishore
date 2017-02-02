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

namespace Unirgy\Dropship\Helper;

use \Magento\Catalog\Model\Product;
use \Magento\Catalog\Model\Product\Type;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\DataObject;
use \Magento\Framework\Event\ManagerInterface;
use \Magento\Sales\Model\Order\Item as OrderItem;
use \Magento\Quote\Model\Quote\Address\Item as AddressItem;
use \Magento\Quote\Model\Quote\Item as QuoteItem;
use \Magento\Quote\Model\Quote\Item\AbstractItem;
use \Magento\Store\Model\StoreManagerInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Model\Source;
use \Unirgy\Dropship\Model\Vendor\Statement;
use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class Item extends AbstractHelper
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Unirgy\Dropship\Model\Source
     */
    protected $_src;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    public function __construct(
        Context $context,
        HelperData $helper,
        StoreManagerInterface $storeManager,
        \Unirgy\Dropship\Model\Source $source
    )
    {
        $this->_hlp = $helper;
        $this->_storeManager = $storeManager;
        $this->_src = $source;

        parent::__construct($context);
    }

    const SKIP_STOCK_CHECK_VENDOR_OPTION   = 'udropship_skip_stock_check';
    const PRIORITY_UDROPSHIP_VENDOR_OPTION = 'priority_udropship_vendor';
    const FORCED_UDROPSHIP_VENDOR_OPTION   = 'forced_udropship_vendor';
    const STICKED_UDROPSHIP_VENDOR_OPTION  = 'sticked_udropship_vendor';
    const UDROPSHIP_VENDOR_OPTION          = 'udropship_vendor';

    public function getSkipStockCheckVendorOption($item)
    {
        return $this->_getItemOption($item, self::SKIP_STOCK_CHECK_VENDOR_OPTION);
    }
    public function setSkipStockCheckVendorOption($item, $flag)
    {
        $this->_saveItemOption($item, self::SKIP_STOCK_CHECK_VENDOR_OPTION, $flag, false);
        return $this;
    }
    public function deleteSkipStockCheckVendorOption($item)
    {
        $this->deleteItemOption($item, self::SKIP_STOCK_CHECK_VENDOR_OPTION);
        return $this;
    }

    /* priority vendor option methods */
    public function getPriorityVendorIdOption($item)
    {
        return $this->_getItemOption($item, self::PRIORITY_UDROPSHIP_VENDOR_OPTION);
    }
    public function setPriorityVendorIdOption($item, $vId, $visible=false)
    {
        $this->_saveItemOption($item, self::PRIORITY_UDROPSHIP_VENDOR_OPTION, $vId, false);
        if ($visible) {
            $this->setVisiblePriorityVendorIdOption($item, $vId);
        }
        return $this;
    }
    public function setVisiblePriorityVendorIdOption($item, $vId)
    {
        $this->_saveVisibleVendorIdOption($item, self::PRIORITY_UDROPSHIP_VENDOR_OPTION, $vId);
        return $this;
    }
    public function deletePriorityVendorIdOption($item)
    {
        $this->deleteItemOption($item, self::PRIORITY_UDROPSHIP_VENDOR_OPTION);
        $this->deleteVisiblePriorityVendorIdOption($item);
        return $this;
    }
    public function deleteVisiblePriorityVendorIdOption($item)
    {
        $this->_deleteVisibleVendorIdOption($item, self::PRIORITY_UDROPSHIP_VENDOR_OPTION);
        return $this;
    }

    /* forced vendor option methods */
    public function getForcedVendorIdOption($item)
    {
        return $this->_getItemOption($item, self::FORCED_UDROPSHIP_VENDOR_OPTION);
    }
    public function setForcedVendorIdOption($item, $vId, $visible=false)
    {
        $this->_saveItemOption($item, self::FORCED_UDROPSHIP_VENDOR_OPTION, $vId, false);
        if ($visible) {
            $this->setVisibleForcedVendorIdOption($item, $vId);
        }
        return $this;
    }
    public function setVisibleForcedVendorIdOption($item, $vId)
    {
        $this->_saveVisibleVendorIdOption($item, self::FORCED_UDROPSHIP_VENDOR_OPTION, $vId);
        return $this;
    }
    public function deleteForcedVendorIdOption($item)
    {
        $this->deleteItemOption($item, self::FORCED_UDROPSHIP_VENDOR_OPTION);
        $this->deleteVisibleForcedVendorIdOption($item);
        return $this;
    }
    public function deleteVisibleForcedVendorIdOption($item)
    {
        $this->_deleteVisibleVendorIdOption($item, self::FORCED_UDROPSHIP_VENDOR_OPTION);
        return $this;
    }

    /* sticked vendor option methods */
    public function getStickedVendorIdOption($item)
    {
        return $this->_getItemOption($item, self::STICKED_UDROPSHIP_VENDOR_OPTION);
    }
    public function setStickedVendorIdOption($item, $vId, $visible=false)
    {
        $this->_saveItemOption($item, self::STICKED_UDROPSHIP_VENDOR_OPTION, $vId, false);
        if ($visible) {
            $this->setVisibleStickedVendorIdOption($item, $vId);
        }
        return $this;
    }
    public function setVisibleStickedVendorIdOption($item, $vId)
    {
        $this->_saveVisibleVendorIdOption($item, self::STICKED_UDROPSHIP_VENDOR_OPTION, $vId);
        return $this;
    }
    public function deleteStickedVendorIdOption($item)
    {
        $this->deleteItemOption($item, self::STICKED_UDROPSHIP_VENDOR_OPTION);
        $this->deleteVisibleStickedVendorIdOption($item);
        return $this;
    }
    public function deleteVisibleStickedVendorIdOption($item)
    {
        $this->_deleteVisibleVendorIdOption($item, self::STICKED_UDROPSHIP_VENDOR_OPTION);
        return $this;
    }

    /* general vendor option methods */
    public function getVendorIdOption($item)
    {
        return $this->_getItemOption($item, self::UDROPSHIP_VENDOR_OPTION);
    }
    public function setVendorIdOption($item, $vId, $visible=false)
    {
        $this->saveItemOption($item, self::UDROPSHIP_VENDOR_OPTION, $vId, false);
        if ($visible) {
            $this->setVisibleVendorIdOption($item, $vId);
        }
        return $this;
    }
    public function setVisibleVendorIdOption($item, $vId)
    {
        $this->_saveVisibleVendorIdOption($item, self::UDROPSHIP_VENDOR_OPTION, $vId);
        return $this;
    }
    public function deleteVendorIdOption($item)
    {
        $this->deleteItemOption($item, self::UDROPSHIP_VENDOR_OPTION);
        $this->deleteVisibleVendorIdOption($item);
        return $this;
    }
    public function deleteVisibleVendorIdOption($item)
    {
        $this->_deleteVisibleVendorIdOption($item, self::UDROPSHIP_VENDOR_OPTION);
        return $this;
    }



    protected function _deleteVisibleVendorIdOption($item, $optCode)
    {
        $addOptions = $this->getAdditionalOptions($item);
        if (!empty($addOptions) && is_string($addOptions)) {
            $addOptions = unserialize($addOptions);
        }
        if (!is_array($addOptions)) {
            $addOptions = array();
        }
        foreach ($addOptions as $idx => $option) {
            if (!empty($option['code']) && $optCode==$option['code']) {
                $vendorOptionIdx = $idx;
                break;
            }
        }
        if (isset($vendorOptionIdx)) unset($addOptions[$vendorOptionIdx]);
        $this->saveAdditionalOptions($item, $addOptions);
        return $this;
    }

    protected function _saveVisibleVendorIdOption($item, $optCode, $value)
    {
        $addOptions = $this->getAdditionalOptions($item);
        if (!empty($addOptions) && is_string($addOptions)) {
            $addOptions = unserialize($addOptions);
        }
        if (!is_array($addOptions)) {
            $addOptions = array();
        }
        foreach ($addOptions as $idx => $option) {
            if (!empty($option['code']) && $optCode==$option['code']) {
                $vendorOptionIdx = $idx;
                break;
            }
        }
        $vendorOption['code']  = $optCode;
        $vendorOption['label'] = (string)__('Vendor');
        $vendorOption['value'] = $this->_hlp->getVendor($value)->getVendorName();
        if (isset($vendorOptionIdx)) {
            $addOptions[$vendorOptionIdx] = $vendorOption;
        } else {
            $addOptions[] = $vendorOption;
        }
        $this->saveAdditionalOptions($item, $addOptions);
        return $this;
    }
    public function getAdditionalOptions($item)
    {
        return $this->_getItemOption($item, 'additional_options');
    }
    public function getItemOption($item, $code)
    {
        return $this->_getItemOption($item, $code);
    }
    protected function _getItemOption($item, $code)
    {
        $optValue = null;
        if ($item instanceof Product
            && $item->getCustomOption($code)
        ) {
            $optValue = $item->getCustomOption($code)->getValue();
        } elseif ($item instanceof QuoteItem
            && $item->getOptionByCode($code)
        ) {
            $optValue = $item->getOptionByCode($code)->getValue();
        } elseif ($item instanceof AddressItem && $item->getQuoteItem()
            && $item->getQuoteItem()->getOptionByCode($code)
        ) {
            $optValue = $item->getQuoteItem()->getOptionByCode($code)->getValue();
        } elseif ($item instanceof OrderItem) {
            $options = $item->getProductOptions();
            if (isset($options[$code])) {
                $optValue = $options[$code];
            }
        } elseif ($item instanceof DataObject && $item->getOrderItem()) {
            $options = $item->getOrderItem()->getProductOptions();
            if (isset($options[$code])) {
                $optValue = $options[$code];
            }
        }
        return $optValue;
    }
    public function saveAdditionalOptions($item, $options)
    {
        return $this->_saveItemOption($item, 'additional_options', $options, true);
    }
    public function saveItemOption($item, $code, $value, $serialize)
    {
        return $this->_saveItemOption($item, $code, $value, $serialize);
    }
    protected function _saveItemOption($item, $code, $value, $serialize)
    {
        if ($item->isDeleted()) return false;
        $currentTime = $this->_hlp->now();
        if ($item instanceof Product) {
            if ($item->getCustomOption($code)) {
                $item->getCustomOption($code)->setValue($serialize ? serialize($value) : $value);
            } else {
                $item->addCustomOption($code, $serialize ? serialize($value) : $value);
            }
            $item->setUpdatedAt($currentTime);
        } elseif ($item instanceof QuoteItem) {
            $optionsByCode = $item->getOptionsByCode();
            if (isset($optionsByCode[$code])) {
                $optionsByCode[$code]->isDeleted(false);
                $optionsByCode[$code]->setValue($serialize ? serialize($value) : $value);
            } else {
                $item->addOption(array(
                    'product' => $item->getProduct(),
                    'product_id' => $item->getProduct()->getId(),
                    'code' => $code,
                    'value' => $serialize ? serialize($value) : $value
                ));
            }
            $item->setUpdatedAt($currentTime);
        } elseif ($item instanceof AddressItem && $item->getQuoteItem()) {
            $optionsByCode = $item->getQuoteItem()->getOptionsByCode();
            if (isset($optionsByCode[$code])) {
                $optionsByCode[$code]->isDeleted(false);
                $optionsByCode[$code]->setValue($serialize ? serialize($value) : $value);
            } else {
                $item->getQuoteItem()->addOption(array(
                    'product' => $item->getQuoteItem()->getProduct(),
                    'product_id' => $item->getQuoteItem()->getProduct()->getId(),
                    'code' => $code,
                    'value' => $serialize ? serialize($value) : $value
                ));
            }
            $item->getQuoteItem()->setUpdatedAt($currentTime);
        } elseif ($item instanceof OrderItem) {
            $options = $item->getProductOptions();
            $options[$code] = $value;
            $item->setProductOptions($options);
            $item->setUpdatedAt($currentTime);
        } elseif ($item instanceof DataObject && $item->getOrderItem()) {
            $options = $item->getOrderItem()->getProductOptions();
            $options[$code] = $value;
            $item->getOrderItem()->setProductOptions($options);
            $item->getOrderItem()->setUpdatedAt($currentTime);
        }
        return $value;
    }
    public function deleteItemOption($item, $code)
    {
        return $this->_deleteItemOption($item, $code);
    }
    protected function _deleteItemOption($item, $code)
    {
        if ($item instanceof Product) {
            $customOptions = $item->getCustomOptions();
            unset($customOptions[$code]);
            $item->setCustomOptions($customOptions);
        } elseif ($item instanceof QuoteItem) {
            $item->removeOption($code);
        } elseif ($item instanceof AddressItem && $item->getQuoteItem()) {
            $item->getQuoteItem()->removeOption($code);
        } elseif ($item instanceof OrderItem) {
            $options = $item->getProductOptions();
            unset($options[$code]);
            $item->setProductOptions($options);
        } elseif ($item instanceof DataObject && $item->getOrderItem()) {
            $options = $item->getOrderItem()->getProductOptions();
            unset($options[$code]);
            $item->getOrderItem()->setProductOptions($options);
        }
        return $this;
    }

    public function getUdropshipVendor($item)
    {
        $vId = $item instanceof AddressItem
            ? $item->getQuoteItem()->getUdropshipVendor()
            : $item->getUdropshipVendor();
        return $vId;
    }
    public function setUdropshipVendor($item, $vId)
    {
        $oldVendorId = $item->getUdropshipVendor();
        $item->setUdropshipVendor($vId);
        $this->_eventManager->dispatch('udropship_quote_item_setUdropshipVendor',
            array('item'=>$item, 'old_vendor_id'=>$oldVendorId, 'new_vendor_id'=>$vId)
        );
        return $this;
    }

    public function compareQuoteItems($item1, $item2)
    {
        if ($item1->getProductId() != $item2->getProductId()) {
            return false;
        }
        foreach ($item1->getOptions() as $option) {
            if ($option->isDeleted() || in_array($option->getCode(), array('info_buyRequest'))) {
                continue;
            }
            if ($item2Option = $item2->getOptionByCode($option->getCode())) {
                $item2OptionValue = $item2Option->getValue();
                $optionValue     = $option->getValue();

                // dispose of some options params, that can cramp comparing of arrays
                if (is_string($item2OptionValue) && is_string($optionValue)) {
                    $_itemOptionValue = @unserialize($item2OptionValue);
                    $_optionValue     = @unserialize($optionValue);
                    if (is_array($_itemOptionValue) && is_array($_optionValue)) {
                        $item2OptionValue = $_itemOptionValue;
                        $optionValue     = $_optionValue;
                        // looks like it does not break bundle selection qty
                        unset($item2OptionValue['qty'], $item2OptionValue['uenc'], $optionValue['qty'], $optionValue['uenc']);
                    }
                }

                if ($item2OptionValue != $optionValue) {
                    return false;
                }
            }
            else {
                return false;
            }
        }
        return true;
    }

    public function getQuote($item)
    {
        $quote = false;
        if ($item instanceof AbstractItem) {
            $quote = $item->getQuote();
        } elseif (is_array($item) || $item instanceof Traversable) {
            foreach ($item as $_item) {
                $quote = $_item->getQuote();
                break;
            }
        }
        return $quote;
    }
    public function getAddress($item)
    {
        $address = false;
        if ($item instanceof AbstractItem) {
            $quote = $item->getQuote();
            $address = $item->getAddress();
        } elseif (is_array($item) || $item instanceof Traversable) {
            foreach ($item as $_item) {
                $quote = $_item->getQuote();
                $address = $_item->getAddress();
                break;
            }
        }
        if ($quote instanceof DataObject && !$address) {
            $address = $quote->getShippingAddress();
        }
        return $address;
    }

    public function createClonedQuoteItem($item, $qty, $quote=null)
    {
        /* @var \Magento\Catalog\Model\Product $product */
        $product = $this->_hlp->createObj('\Magento\Catalog\Model\Product')->load($item->getProductId());
        $product
            ->setStoreId($this->_storeManager->getStore()->getId())
            ->load($item->getProductId());
        if (!$product->getId()) {
            return false;
        }

        $info = $this->getItemOption($item, 'info_buyRequest');
        $info = new DataObject(unserialize($info));
        $info->setQty($qty);

        if (!$quote) $quote = $item->getQuote();

        $item = $quote->addProduct($product, $info);
        return $item;
    }

    public function attachOrderItemPoInfo($order)
    {
        if ($this->_hlp->isUdpoActive()) {
            $udpoSrc = $this->_hlp->getObj('\Unirgy\DropshipPo\Model\Source');
            $statuses = $udpoSrc->setPath('po_statuses')->toOptionHash();
        } else {
            $statuses = $this->_src->setPath('shipment_statuses')->toOptionHash();
        }
        /** @var \Unirgy\Dropship\Model\ResourceModel\Helper $rHlp */
        $rHlp = $this->_hlp->getObj('\Unirgy\Dropship\Model\ResourceModel\Helper');
        $poInfo = $rHlp->getOrderItemPoInfo($order);
        $vendors = $this->_src->getVendors(true);
        foreach ($poInfo as $poi) {
            $optKey = 'udropship_poinfo';
            $optVal = $poi['item_id'].'-'.$poi['increment_id'];
            $item = $order->getItemById($poi['item_id']);
            if ($item->isDummy(true)) continue;
            $addOptions = $this->getAdditionalOptions($item);
            if (!empty($addOptions) && is_string($addOptions)) {
                $addOptions = unserialize($addOptions);
            }
            if (!is_array($addOptions)) {
                $addOptions = array();
            }
            foreach ($addOptions as $idx => $option) {
                if (@$option[$optKey] == $optVal) {
                    $vendorOptionIdx = $idx;
                    break;
                }
            }
            $vendorOption['label'] = (string)__('PO #%1 [%2]', $poi['increment_id'], @$statuses[$poi['udropship_status']]);
            //$vendorOption['value'] = __('%s (qty: x%s) [status: %s]', @$vendors[$poi['udropship_vendor']], 1*$poi['qty'], @$statuses[$poi['udropship_status']]);
            $vendorOption['value'] = (string)__('%1 (qty: %2)', @$vendors[$poi['udropship_vendor']], 1*$poi['qty']);
            if (isset($vendorOptionIdx)) {
                $addOptions[$vendorOptionIdx] = $vendorOption;
            } else {
                $addOptions[] = $vendorOption;
            }
            $this->saveAdditionalOptions($item, $addOptions);
        }
    }

    public function attachOrderItemVendorSkuInfo($item, $oItem)
    {
        $optKey = 'vendorsku_info';
        $optVal = $item->getVendorSku() ? $item->getVendorSku() : $item->getSku();
        $addOptions = $this->getAdditionalOptions($oItem);
        if (!empty($addOptions) && is_string($addOptions)) {
            $addOptions = unserialize($addOptions);
        }
        if (!is_array($addOptions)) {
            $addOptions = array();
        }
        foreach ($addOptions as $idx => $option) {
            if (@$option[$optKey] == $optVal) {
                $vendorOptionIdx = $idx;
                break;
            }
        }
        $vendorOption['label'] = (string)__('Vendor SKU:');
        $vendorOption['value'] = $optVal;
        if (isset($vendorOptionIdx)) {
            $addOptions[$vendorOptionIdx] = $vendorOption;
        } else {
            $addOptions[] = $vendorOption;
        }
        $this->saveAdditionalOptions($oItem, $addOptions);
    }

    public function getItemVendor($item, $fallback=false)
    {
        $storeId = $item->getQuote() ? $item->getQuote()->getStoreId() : null;
        $hlp = $this->_hlp;
        $localVendorId = $hlp->getLocalVendorId($storeId);
        $vId = $item->getUdropshipVendor();
        $vendor = $hlp->getVendor($vId);
        if ((!$vId || !$vendor->getId()) && $fallback) {
            $vId = $localVendorId;
            $vendor = $hlp->getVendor($vId);
        }
        return $vendor;
    }

    public function getChildInfoKeys()
    {
        return array('base_cost');
    }
    public function getShipInfoKeys()
    {
        return array('full_row_weight','row_weight');
    }
    public function getPriceInfoKeys()
    {
        return array('base_row_total','base_discount_amount');
    }

    public function addChildInfo($parent, $child, &$info)
    {
        $iHlp = $this;
        foreach ($iHlp->getChildInfoKeys() as $pKey) {
            $info[$pKey] = @$info[$pKey]+$child->getDataUsingMethod($pKey);
        }
        if (!$parent->getProduct()->getWeightType()) {
            foreach ($iHlp->getShipInfoKeys() as $pKey) {
                $info[$pKey] = @$info[$pKey]+$child->getDataUsingMethod($pKey);
            }
        }
        if ($child->isChildrenCalculated()) {
            foreach ($iHlp->getPriceInfoKeys() as $pKey) {
                $info[$pKey] = @$info[$pKey]+$child->getDataUsingMethod($pKey);
            }
        }
        return $this;
    }

    public function getChildrenInfoByVendor($item, $vId=null)
    {
        $infoByVendor = array();
        foreach ($item->getChildren() as $child) {
            $vendor = $this->getItemVendor($child, true);
            $_vId = $vendor->getId();
            if ($vId && $vId!=$_vId) continue;
            if (empty($infoByVendor[$_vId ])) {
                $infoByVendor[$_vId ] = array();
            }
            $this->addChildInfo($item, $child, $infoByVendor[$_vId]);
        }
        foreach ($infoByVendor as &$info) {
            $info = $info+$this->getItemInfo($item);
        }
        unset($info);
        return !$vId ? $infoByVendor : (!empty($infoByVendor[$vId]) ? $infoByVendor[$vId] : array());
    }

    public function getItemInfo($item)
    {
        $iHlp = $this;
        $info = array();
        if ($item->getHasChildren()) {
            foreach ($item->getChildren() as $child) {
                foreach ($iHlp->getChildInfoKeys() as $pKey) {
                    $info[$pKey] = @$info[$pKey]+$child->getDataUsingMethod($pKey);
                }
            }
        } else {
            foreach ($iHlp->getChildInfoKeys() as $pKey) {
                $info[$pKey] = $item->getDataUsingMethod($pKey);
            }
        }
        foreach ($iHlp->getShipInfoKeys() as $pKey) {
            $info[$pKey] = $item->getDataUsingMethod($pKey);
        }
        foreach ($iHlp->getPriceInfoKeys() as $pKey) {
            $info[$pKey] = $item->getDataUsingMethod($pKey);
        }
        return $info;
    }

    public function isVirtual($item)
    {
        return $item->getProduct() instanceof Product
            ? $item->getProduct()->getIsVirtual()
            : $item->getIsVirtual();
    }

    public function processSameVendorLimitation($items, &$requests)
    {
        if (!is_array($requests)) return $this;
        $forcedSameVendor = array();
        foreach ($items as $item) {
            if ($item->getHasChildren() && !$item->isShipSeparately()) {
                $children = $item->getChildren() ? $item->getChildren() : $item->getChildrenItems();
                foreach ($children as $child) {
                    foreach ($children as $child2) {
                        $pId = $child->getProductId();
                        $pId2 = $child2->getProductId();
                        $forcedSameVendor[$pId][$pId2] = $pId2;
                    }
                }
            }
        }
        $vIdsByPid = array();
        foreach ($requests as $request) {
            if (empty($request['products'])) continue;
            foreach ($request['products'] as $pId=>$rpData) {
                $_curVids = isset($rpData['vendors']) ? array_keys($rpData['vendors']) : array();
                foreach ($_curVids as $_rVid) {
                    $vIdsByPid[$pId][$_rVid] = $_rVid;
                }
            }
        }
        foreach ($forcedSameVendor as $fPid=>$rfPids) {
            foreach ($rfPids as $rfPid) {
                $itsArr1 = !empty($vIdsByPid[$fPid]) ? $vIdsByPid[$fPid] : array();
                $itsArr2 = !empty($vIdsByPid[$rfPid]) ? $vIdsByPid[$rfPid] : array();
                $vIdsByPid[$fPid] = array_intersect_key($itsArr1, $itsArr2);
            }
        }
        foreach ($requests as &$request) {
            if (empty($request['products'])) continue;
            foreach ($request['products'] as $pId=>$rpData) {
                $_fVids = isset($vIdsByPid[$pId]) ? $vIdsByPid[$pId] : array();
                $_curVids = isset($rpData['vendors']) ? array_keys($rpData['vendors']) : array();
                $_rmVids = array_diff($_curVids, $_fVids);
                foreach ($_rmVids as $_rmVid) {
                    unset($request['products'][$pId]['vendors'][$_rmVid]);
                }
            }
        }
        unset($request);
        return $this;
    }

    public function initBaseCosts($items)
    {
        foreach ($items as $item) {
            $product = $item->getProduct();
            $quote = $item->getQuote();
            $sId = $quote->getStoreId();

            $specialCost = $this->_hlp->getScopeConfig('udropship/vendor/special_cost_attribute');
            if ($specialCost && ($specialCost = $product->getData($specialCost))) {
                $baseCost = $item->getBaseCost();
                $specialFrom = $product->getSpecialFromDate();
                $specialTo = $product->getSpecialToDate();
                if ($this->_hlp->isScopeDateInInterval($sId, $specialFrom, $specialTo)) {
                    $item->setBaseCost(min($baseCost, $specialCost));
                };
            }

            if (($parent = $item->getParentItem()) && !$item->getBaseCost()) {
                if ($parent->getProductType() == Configurable::TYPE_CODE) {
                    $item->setBaseCost($parent->getPrice());
                } else {
                    $item->setBaseCost($product->getPrice());
                }
            }

            if (($parent = $item->getParentItem()) && $parent->getProductType() == Configurable::TYPE_CODE) {
                $parent->setUdropshipVendor($item->getUdropshipVendor());
                $parent->setBaseCost($item->getBaseCost());
            }

            if (!$this->_hlp->isUdmultiActive()) {
                $p = $product;
                $vcKey = sprintf('multi_vendor_data/%s/vendor_cost', $item->getUdropshipVendor());
                if (($vc = $p->getData($vcKey)) && $vc>0) {
                    $item->setBaseCost($vc);
                    if (($parent = $item->getParentItem()) && $parent->getProductType() == Configurable::TYPE_CODE) {
                        $parent->setBaseCost($vc);
                    }
                }
            }
        }
    }

    public function isShipDummy($item)
    {
        if ($item instanceof AbstractItem) {
            if ($item->getHasChildren() && $item->isShipSeparately()) {
                return true;
            }

            if ($item->getHasChildren() && !$item->isShipSeparately()) {
                return false;
            }

            if ($item->getParentItem() && $item->isShipSeparately()) {
                return false;
            }

            if ($item->getParentItem() && !$item->isShipSeparately()) {
                return true;
            }
        } else {
            return $item->isDummy(true);
        }
    }

    public function hideVendorIdOption($po)
    {
        foreach ($po->getAllItems() as $poItem) {
            $item = $poItem->getOrderItem();
            $this->deleteVisibleVendorIdOption($item);
        }
    }

    public function initPoTotals($po)
    {
        $hlp = $this->_hlp;
        $isTierCom = $hlp->isModuleActive('Unirgy_DropshipTierCommission');
        $vendor = $hlp->getVendor($po->getUdropshipVendor());
        $order = $po->getOrder();
        /* @var \Unirgy\Dropship\Model\Vendor\Statement $statement */
        $statement = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor\Statement');
        $statement->setVendor($vendor)->setVendorId($vendor->getId());
        $totals = $statement->getEmptyTotals(true);
        $totals_amount = $statement->getEmptyTotals();
        $hlp->collectPoAdjustments(array($po), true);
        $stOrders = array();
        if ($isTierCom) {
            $onlySubtotal = false;
            foreach ($po->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) continue;
                $stOrder = $statement->initPoItem($item, $onlySubtotal);
                $onlySubtotal = true;
                $stOrder = $statement->calculateOrder($stOrder);
                $totals_amount = $statement->accumulateOrder($stOrder, $totals_amount);
                $stOrders[$item->getId()] = $stOrder;
            }
        } else {
            $stOrder = $statement->initOrder($po);
            $stOrder = $statement->calculateOrder($stOrder);
            $totals_amount = $statement->accumulateOrder($stOrder, $totals_amount);
        }
        $this->formatOrderAmounts($order, $totals, $totals_amount, 'merge');
        $poTotals = array();
        foreach ($totals as $tKey=>$tValue) {
            $tLabel = false;
            switch ($tKey) {
                case 'subtotal':
                    $tLabel = (string)__('Subtotal');
                    break;
                case 'com_percent':
                    if (!$isTierCom) {
                        $tLabel = (string)__('Commission Percent');
                    }
                    break;
                case 'trans_fee':
                    $tLabel = (string)__('Transaction Fee');
                    break;
                case 'com_amount':
                    $tLabel = (string)__('Commission Amount');
                    break;
                case 'adj_amount':
                    if ($tValue>0) {
                        $tLabel = (string)__('Adjustment');
                    }
                    break;
                case 'total_payout':
                    $tLabel = (string)__('Total Payout');
                    break;
                case 'tax':
                    if (in_array($vendor->getStatementTaxInPayout(), array('', 'include'))) {
                        $tLabel = (string)__('Tax Amount');
                    }
                    break;
                case 'discount':
                    if (in_array($vendor->getStatementDiscountInPayout(), array('', 'include'))) {
                        $tLabel = (string)__('Discount');
                    }
                    break;
                case 'shipping':
                    if (in_array($vendor->getStatementShippingInPayout(), array('', 'include'))) {
                        $tLabel = (string)__('Shipping');
                    }
                    break;
            }
            if ($tLabel) {
                $poTotals[] = array(
                    'label' => $tLabel,
                    'value' => $tValue
                );
            }
        }
        $po->setUdropshipTotalAmounts($totals_amount);
        $po->setUdropshipTotals($poTotals);

        foreach ($po->getAllItems() as $poItem) {
            if ($poItem->getOrderItem()->getParentItem()) continue;
            $item = $poItem->getOrderItem();
            $itemAmounts = $addOptions = array();
            $itemAmounts['cost'] = $poItem->getBaseCost();
            $itemAmounts['row_cost'] = $poItem->getBaseCost()*$poItem->getQty();
            $itemAmounts['price'] = $item->getBasePrice();
            $itemAmounts['row_total'] = $item->getBasePrice()*$poItem->getQty();
            if ($vendor->getStatementSubtotalBase() == 'cost') {
                $addOptions[] = array(
                    'label' => (string)__('Cost'),
                    'value' => $this->formatBasePrice($order, $poItem->getBaseCost())
                );
                if ($poItem->getQty()>1) {
                    $addOptions[] = array(
                        'label' => (string)__('Row Cost'),
                        'value' => $this->formatBasePrice($order, $poItem->getBaseCost()*$poItem->getQty())
                    );
                }
            } else {
                $addOptions[] = array(
                    'label' => (string)__('Price'),
                    'value' => $this->formatBasePrice($order, $item->getBasePrice())
                );
                if ($poItem->getQty()>1) {
                    $addOptions[] = array(
                        'label' => (string)__('Row Total'),
                        'value' => $this->formatBasePrice($order, $item->getBasePrice()*$poItem->getQty())
                    );
                }
            }
            $iTax = $item->getBaseTaxAmount()/max(1,$item->getQtyOrdered());
            $iTax = $iTax*$poItem->getQty();
            $itemAmounts['tax'] = $iTax;
            if ($item->getBaseTaxAmount() && in_array($vendor->getStatementTaxInPayout(), array('', 'include'))) {
                $addOptions[] = array(
                    'label' => (string)__('Tax Amount'),
                    'value' => $this->formatBasePrice($order, $iTax)
                );
            }
            $iDiscount = $item->getBaseDiscountAmount()/max(1,$item->getQtyOrdered());
            $iDiscount = $iDiscount*$poItem->getQty();
            $itemAmounts['discount'] = $iDiscount;
            if ($item->getBaseDiscountAmount() && in_array($vendor->getStatementDiscountInPayout(), array('', 'include'))) {
                $addOptions[] = array(
                    'label' => (string)__('Discount'),
                    'value' => $this->formatBasePrice($order, $iDiscount)
                );
            }
            if ($isTierCom) {
                $itemAmounts['com_percent'] = $stOrders[$poItem->getId()]['com_percent'];
                $itemAmounts['com_amount'] = $stOrders[$poItem->getId()]['amounts']['com_amount'];
                if ($isTierCom && isset($stOrders[$poItem->getId()]['com_percent']) && $stOrders[$poItem->getId()]['com_percent']>0) {
                    $addOptions[] = array(
                        'label' => (string)__('Commission Percent'),
                        'value' => sprintf('%s%%', $stOrders[$poItem->getId()]['com_percent'])
                    );
                    if (isset($stOrders[$poItem->getId()]['amounts']['com_amount'])) {
                    $addOptions[] = array(
                        'label' => (string)__('Commission Amount'),
                        'value' => $this->formatBasePrice($order, $stOrders[$poItem->getId()]['amounts']['com_amount'])
                    );
                    }
                }
            }
            $poItem->setUdropshipTotalAmounts($itemAmounts);
            $poItem->setUdropshipTotals($addOptions);
            //$this->saveAdditionalOptions($item, $addOptions);
        }
    }

    public function formatBasePrice($order, $cost)
    {
        if (!$order->getBaseCurrency()) {
            /* @var \Magento\Directory\Model\Currency $baseCurrency */
            $baseCurrency = $this->_hlp->createObj('\Magento\Directory\Model\Currency');
            $baseCurrency->load($order->getBaseCurrencyCode());
            $order->setBaseCurrency($baseCurrency);
        }
        return $order->getBaseCurrency()->formatTxt($cost);
    }

    public function formatOrderAmounts($order, &$data, $defaultAmounts=null, $useDefault=false)
    {
        $iter = (is_null($defaultAmounts) ? $data : $defaultAmounts);
        if (is_array($iter)) {
            foreach ($iter as $k => $v) {
                if ($useDefault == 'merge' || $useDefault && !isset($data[$k])) {
                    $data[$k] = $this->formatBasePrice($order, (float)$v);
                } elseif (isset($data[$k])) {
                    $data[$k] = $this->formatBasePrice($order, (float)$data[$k]);
                }
            }
        }
        return $this;
    }
    protected $_cartUpdateActionFlag=false;
    public function getIsCartUpdateActionFlag()
    {
        return $this->_cartUpdateActionFlag;
    }
    public function setIsCartUpdateActionFlag($flag)
    {
        $this->_cartUpdateActionFlag=(bool)$flag;
        return $this;
    }
    protected $_throwOnQuoteError=false;
    public function getIsThrowOnQuoteError()
    {
        return $this->_throwOnQuoteError;
    }
    public function setIsThrowOnQuoteError($flag)
    {
        $this->_throwOnQuoteError=(bool)$flag;
        return $this;
    }
}