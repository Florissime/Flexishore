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

namespace Unirgy\Dropship\Helper\ProtectedCode;

use \Magento\Framework\DataObject;
use \Magento\Sales\Model\Order as ModelOrder;
use \Magento\Shipping\Model\Carrier\AbstractCarrier;
use \Magento\Shipping\Model\Config;
use \Magento\Shipping\Model\Shipping;
use \Unirgy\Dropship\Model\Source;
use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use \Magento\Catalog\Model\Product\Type\AbstractType as ProductTypeAbstract;
use \Unirgy\SimpleLicense\Helper\ProtectedCode as SimpleLicenseProtectedCode;

class OrderSave
{
    /**
     * @var \Unirgy\Dropship\Helper\ProtectedCode
     */
    protected $_hlpPr;
    protected $_hlp;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Unirgy\Dropship\Helper\ProtectedCode $helperProtectedCode,
        \Unirgy\Dropship\Observer\Context $context,
        array $data = []
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_hlpPr = $helperProtectedCode;
    }

    /**
     * Create shipments based on dropship vendors and notify vendors
     *
     * @param mixed $observer
     */
    public function sales_order_save_after($observer)
    {
        $order = $observer instanceof \Magento\Sales\Model\Order ? $observer : $observer->getEvent()->getOrder();
        $store = $order->getStore();
        $storeId = $store->getId();
        $enableVirtual = $this->_hlp->getScopeConfig('udropship/misc/enable_virtual', $order->getStoreId());
        $forcedActive = $this->_hlp->getScopeFlag('carriers/udropship/force_active', $store);
        if ($this->_hlp->isUdpoActive()) {
            $enableVirtual = true;
        }
        $shippingMethod = $this->_hlp->explodeOrderShippingMethod($order);
#$this->_logLoggerInterface->log($order->debug());
        $shippingDetails = $order->getUdropshipShippingDetails();
        if (!$shippingDetails && !$enableVirtual && !$forcedActive) {
            return; // not a dropship order
        }
        /*
        if ($shippingMethod[0]!=='udropship' && $shippingMethod[0]!=='udsplit') {
            return;
        }
        */
        /** @var \Unirgy\Dropship\Model\ResourceModel\Helper $rHlp */
        $rHlp = $this->_hlp->getObj('\Unirgy\Dropship\Model\ResourceModel\Helper');
        $oUdStatus = $this->_hlp->getScopeFlag('udropship/admin/for_update_split_check')
            ? $rHlp->loadModelFieldForUpdate($order, 'udropship_status')
            : $rHlp->loadModelField($order, 'udropship_status');
        if ($order->getUdropshipStatus()==Source::ORDER_STATUS_PENDING
            && $oUdStatus!=Source::ORDER_STATUS_PENDING
        ) {
            $order->setUdropshipStatus($oUdStatus);
        }
        if ($order->getUdropshipStatus()!=Source::ORDER_STATUS_PENDING) {
            if ($order->getUdropshipStatus()==Source::ORDER_STATUS_NOTIFIED
                && $order->getState()==ModelOrder::STATE_CANCELED) {
                $this->_cancelOrder($order);
            }
            return; // order is already available and was sent to dropshippers
        }

        if (!$this->_hlpPr->fixGoolecheckout($order)) {
            return;
        }
#$this->_logLoggerInterface->log($shippingDetails);
        $this->_setHasMultipleVendors($order);
        // check for configured statuses whether it's time to make it available
        $statuses = explode(',', $this->_hlp->getScopeConfig('udropship/vendor/make_available_to_dropship', $order->getStoreId()));
        if (!in_array($order->getStatus(), $statuses)) {
            return; // no, it's not
        }

        $this->_normalizeOrderShippingDetails($order);
        $oldStatus = $order->getUdropshipStatus();
        try {
            $order->setUdropshipOrderSplitFlag(false);
            $order->setUdropshipStatus(Source::ORDER_STATUS_NOTIFIED);
            $order->getResource()->saveAttribute($order, 'udropship_status');
            $this->_hlpPr->splitOrder($order);
        } catch (\Exception $e) {
            if (!$order->getUdropshipOrderSplitFlag()) {
                $order->setUdropshipStatus($oldStatus);
                $order->getResource()->saveAttribute($order, 'udropship_status');
            }
            throw $e;
        }
    }

    protected function _cancelOrder($order)
    {
        foreach ($order->getShipmentsCollection() as $shipment) {
            $shipment->setUdropshipStatus(Source::SHIPMENT_STATUS_CANCELED)->save();
        }
        $order->setUdropshipStatus(Source::ORDER_STATUS_CANCELED)->save();
        return $this;
    }

    protected function _setHasMultipleVendors($order)
    {
        $items = $order->getAllItems();
        // set $order.has_multiple_vendors for customer email
        $vIds = array();
        foreach ($items as $orderItem) {
            if ($orderItem->getHasChildren()) {
                continue;
            }
            $vId = $orderItem->getUdropshipVendor();
            $vIds[$vId] = true;
        }
        $order->setHasMultipleVendors(sizeof($vIds)>1);
    }

    protected function _normalizeOrderShippingDetails($order)
    {
        $order->setUdropshipShippingDetails(\Zend_Json::encode(array('methods'=>$this->_hlpPr->getOrderVendorRates($order))));
    }
}