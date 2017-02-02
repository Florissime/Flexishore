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

namespace Unirgy\Dropship\Block\Vendor\Shipment;

use \Magento\Framework\Registry;
use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Sales\Block\Items\AbstractItems;
use \Magento\Sales\Model\Order\Shipment;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Helper\Item;
use \Unirgy\Dropship\Model\Session;

class Info extends AbstractItems
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var Item
     */
    protected $_iHlp;

    /**
     * @var \Magento\Shipping\Model\CarrierFactory
     */
    protected $_carrierFactory;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $_shippingConfig;

    public function __construct(
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Magento\Shipping\Model\Config $shippingConfig,
        HelperData $helperData,
        Registry $frameworkRegistry,
        Item $helperItem,
        Context $context,
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        $this->_registry = $frameworkRegistry;
        $this->_iHlp = $helperItem;
        $this->_carrierFactory = $carrierFactory;
        $this->_shippingConfig = $shippingConfig;

        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        Template::_construct();
        //$this->addItemRender('default', 'sales/order_item_renderer_default', 'sales/order/shipment/items/renderer/default.phtml');
    }

    public function getVendor()
    {
        return $this->_hlp->session()->getVendor();
    }
    public function isShowTotals()
    {
        return $this->_hlp->getVendorFallbackFlagField(
            $this->getVendor(),
            'portal_show_totals', 'udropship/vendor/portal_show_totals'
        );
    }

    public function getShipment()
    {
        if (!$this->hasData('shipment')) {
            $id = (int)$this->getRequest()->getParam('id');
            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            $shipment = $this->_hlp->createObj('\Magento\Sales\Model\Order\Shipment')->load($id);
            $this->_registry->register('current_order', $shipment->getOrder());
            $this->setData('shipment', $shipment);
            $this->_hlp->assignVendorSkus($shipment);
            $this->_iHlp->hideVendorIdOption($shipment);
            if ($this->isShowTotals()) {
                $this->_iHlp->initPoTotals($shipment);
            }
        }
        return $this->getData('shipment');
    }

    public function getRemainingWeight()
    {
        $weight = 0;
        $parentItems = array();
        foreach ($this->getShipment()->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();

            $children = $orderItem->getChildrenItems() ? $orderItem->getChildrenItems() : $orderItem->getChildren();
            if ($children) {
                $parentItems[$orderItem->getId()] = $item;
            }
            $__qty = $item->getQty();
            if ($orderItem->isDummy(true)) {
                if (($_parentItem = $orderItem->getParentItem())) {
                    $__qty = $orderItem->getQtyOrdered()/$_parentItem->getQtyOrdered();
                    if (@$parentItems[$_parentItem->getId()]) {
                        $__qty *= $parentItems[$_parentItem->getId()]->getQty();
                    }
                } else {
                    $__qty = max(1,$item->getQty());
                }
            }

            if ($orderItem->getParentItem()) {
                $weightType = $orderItem->getParentItem()->getProductOptionByCode('weight_type');
                if (null !== $weightType && !$weightType) {
                    $weight += $item->getWeight()*$__qty;
                }
            } else {
                $weightType = $orderItem->getProductOptionByCode('weight_type');
                if (null === $weightType || $weightType) {
                    $weight += $item->getWeight()*$__qty;
                }
            }
        }
        foreach ($this->getShipment()->getAllTracks() as $track) {
            $weight -= $track->getWeight();
        }
        return max(0, $weight);
    }

    public function getRemainingValue()
    {
        $value = 0;
        foreach ($this->getShipment()->getAllItems() as $item) {
            $value += $item->getPrice()*$item->getQty();
        }
        foreach ($this->getShipment()->getAllTracks() as $track) {
            $value -= (float)$track->getValue();
        }
        return max(0, $value);
    }

    public function getUdpo($shipment)
    {
        if ($this->_hlp->isUdpoActive()) {
            return $this->_hlp->udpoHlp()->getShipmentPo($shipment);
        } else {
            return false;
        }
    }

    public function getCarriers()
    {
        $carriers = array();
        $carrierInstances = $this->_shippingConfig->getAllCarriers(
            $this->getShipment()->getStoreId()
        );
        $carriers[''] = __('* Use PO carrier *');
        $carriers['custom'] = __('Custom Value');
        foreach ($carrierInstances as $code => $carrier) {
            if ($carrier->isTrackingAvailable()) {
                $carriers[$code] = $carrier->getConfigData('title');
            }
        }
        return $carriers;
    }

    public function getCarrierTitle($code)
    {
        if ($carrier = $this->_carrierFactory->create($code)) {
            return $carrier->getConfigData('title');
        }
        else {
            return __('Custom Value');
        }
        return false;
    }

}
