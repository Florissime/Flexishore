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
use \Magento\Sales\Model\Order\Shipment\Track;
use \Magento\Store\Model\StoreManagerInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Helper\Error;
use \Unirgy\Dropship\Model\Source;

abstract class AbstractObserver extends DataObject
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var Resource
     */
    protected $_rHlp;

    /**
     * @var Config
     */
    protected $_eavConfig;

    /**
     * @var Error
     */
    protected $_helperError;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    protected $_iHlp;

    public function __construct(
        \Unirgy\Dropship\Observer\Context $context,
        array $data = []
    )
    {
        $this->_iHlp = $context->iHlp;
        $this->_hlp = $context->hlp;
        $this->_rHlp = $context->rHlp;
        $this->_eavConfig = $context->eavConfig;
        $this->_helperError = $context->helperError;
        $this->scopeConfig = $context->scopeConfig;
        $this->_storeManager = $context->storeManager;
        $this->_eventManager = $context->eventManager;

        parent::__construct($data);
    }

    public function getIsCartUpdateActionFlag()
    {
        return $this->_iHlp->getIsCartUpdateActionFlag();
    }
    public function setIsCartUpdateActionFlag($flag)
    {
        $this->_iHlp->setIsCartUpdateActionFlag((bool)$flag);
        return $this;
    }
    public function getIsThrowOnQuoteError()
    {
        return $this->_iHlp->getIsThrowOnQuoteError();
    }
    public function setIsThrowOnQuoteError($flag)
    {
        $this->_iHlp->setIsThrowOnQuoteError((bool)$flag);
        return $this;
    }

    public function syncMultiAddressUdropshipVendor($observer)
    {
        if ($observer->getQuote()->getIsMultiShipping()) {
            foreach ($observer->getQuote()->getAllAddresses() as $address) {
                $address->getAllItems();
                $addressItems = $address->getItemsCollection();
                foreach ($addressItems as $addressItem) {
                    if ($addressItem->getQuoteItem()) {
                        $addressItem->setUdropshipVendor($addressItem->getQuoteItem()->getUdropshipVendor());
                    }
                }
            }
        }
    }

    public function cronCollectTracking()
    {
        $statusFilter = array(Source::TRACK_STATUS_PENDING,Source::TRACK_STATUS_READY,Source::TRACK_STATUS_SHIPPED);
        $res  = $this->_rHlp;
        $conn = $res->getMyConnection('sales');

        $sIdsSel = $conn->select()->distinct()
            ->from($res->getTableName('sales_shipment_track'), array('parent_id'))
            ->where('udropship_status in (?)', $statusFilter)
            ->where('next_check<=?', $this->_hlp->now())
            ->limit(50);
        $sIds = $conn->fetchCol($sIdsSel);

        if (!empty($sIds)) {
            /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection $tracks */
            $tracks = $this->_hlp->createObj('\Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection');
            $tracks
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('udropship_status', array('in'=>$statusFilter))
                ->addAttributeToFilter('parent_id', array('in'=>$sIds))
                ->addAttributeToSort('parent_id')
            ;

            try {
                $this->_hlp->collectTracking($tracks);
            } catch (\Exception $e) {
                $tracksByStore = array();
                foreach ($tracks as $track) {
                    $tracksByStore[$track->getShipment()->getOrder()->getStoreId()][] = $track;
                }
                foreach ($tracksByStore as $sId => $_tracks) {
                    $this->_helperError->sendPollTrackingFailedNotification($_tracks, "$e", $sId);
                }
            }
        }

        if (0<$this->_hlp->getScopeConfig('udropship/error_notifications/poll_tracking_limit')) {
            $limit = date('Y-m-d H:i:s', time()-24*60*60*$this->_hlp->getScopeConfig('udropship/error_notifications/poll_tracking_limit'));

            /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection $tracks */
            $tracks = $this->_hlp->createObj('\Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection');
            $tracks
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('udropship_status', 'P')
                ->addAttributeToFilter('created_at', array('datetime'=>true, 'to'=>$limit))
                ->setPageSize(50)
            ;
            $tracksByStore = array();
            foreach ($tracks as $track) {
                $cCode = $track->getCarrierCode();
                if (!$cCode) {
                    continue;
                }
                $vId = $track->getShipment()->getUdropshipVendor();
                $v = $this->_hlp->getVendor($vId);
                if (!$v->getTrackApi($cCode)) {
                    continue;
                }
                $tracksByStore[$track->getShipment()->getOrder()->getStoreId()][] = $track;
            }
            foreach ($tracksByStore as $sId => $_tracks) {
                $this->_helperError->sendPollTrackingLimitExceededNotification($_tracks, $sId);
            }
        }
    }



    public function vendorNotifyLowstock()
    {
        /** @var \Unirgy\Dropship\Model\Vendor\NotifyLowstock $vendorNotifylowstock */
        $vendorNotifylowstock = $this->_hlp->getObj('\Unirgy\Dropship\Model\Vendor\NotifyLowstock');
        $vendorNotifylowstock->vendorNotifyLowstock();
    }
    public function vendorCleanLowstock()
    {
        /** @var \Unirgy\Dropship\Model\Vendor\NotifyLowstock $vendorNotifylowstock */
        $vendorNotifylowstock = $this->_hlp->getObj('\Unirgy\Dropship\Model\Vendor\NotifyLowstock');
        $vendorNotifylowstock->vendorCleanLowstock();
    }

    protected function _sales_order_shipment_save_before($observer, $isStatusEvent)
    {
        $po = $observer->getEvent()->getShipment();
        if ($po->getUdropshipVendor()
            && ($vendor = $this->_hlp->getVendor($po->getUdropshipVendor()))
            && $vendor->getId()
            && (!$po->getStatementDate() || $po->getStatementDate() == '0000-00-00 00:00:00')
            && $vendor->getStatementPoType() == 'shipment'
        ) {
            $stPoStatuses = $vendor->getStatementPoStatus();
            if (!is_array($stPoStatuses)) {
                $stPoStatuses = explode(',', $stPoStatuses);
            }
            if (in_array($po->getUdropshipStatus(), $stPoStatuses)) {
                $po->setStatementDate($this->_hlp->now());
                $po->setUpdatedAt($this->_hlp->now());
                if ($isStatusEvent) {
                    $po->getResource()->saveAttribute($po, 'statement_date');
                    $po->getResource()->saveAttribute($po, 'updated_at');
                }
            }
        }
    }





    public function isInstalled()
    {
        /** @var \Magento\Framework\App\DeploymentConfig $deployment */
        $deployment = $this->_hlp->getObj('Magento\Framework\App\DeploymentConfig');
        return $deployment->isAvailable();
    }

    protected function _initConfigRewrites()
    {
        if (!$this->isInstalled()) return false;
        $paths = $this->getRuntimeProductAttributesConfigPaths();
        $runtimeAttrCodes = array();
        foreach ($paths as $path) {
            $path = str_replace('-', '/', $path);
            if (($attrCode = $this->scopeConfig->getValue($path))
                && $this->_hlp->checkProductCollectionAttribute($attrCode)
            ) {
                $runtimeAttrCodes[$attrCode] = $attrCode;
            }
        }
        if (!empty($runtimeAttrCodes)) {
            $flatAttrNode = $this->_hlp->getScopeConfig('global/catalog/product/flat/attribute_nodes', 'default');
            $flatAttrNode->addChild('udropship_runtime_product_attributes', 'global/udropship/runtime_product_attributes');
            $runtimeAttrCodesParentNode = $this->_hlp->getScopeConfig('global/udropship/runtime_product_attributes', 'default');
            foreach ($runtimeAttrCodes as $runtimeAttrCode) {
                $runtimeAttrCodesParentNode->addChild($runtimeAttrCode);
            }
        }
        if ($this->_hlp->getScopeFlag('udropship/stock/split_bundle_by_vendors')) {
            Mage::getConfig()->setNode('global/models/bundle/rewrite/product_type', 'Unirgy\Dropship\Model\BundleProductType');
        }
        if ($this->_hlp->getScopeConfig('udropship/stock/availability')=='local_if_in_stock') {
            if ($this->_hlp->isEE()
                && $this->_hlp->compareMageVer('1.8.0.0', '1.13.0.0')
            ) {
                Mage::getConfig()->setNode('global/models/enterprise_cataloginventory_resource/rewrite/indexer_stock_default', 'Unirgy\Dropship\Model\StockIndexer\EE11300\Default');

            }
        }
    }

    public function beforeCrontab()
    {
        $this->initConfigRewrites();
    }
    public function initConfigRewrites()
    {
        $this->_eventManager->dispatch('udropship_init_config_rewrites', array());
    }

    /**
     * @param $order \Magento\Sales\Model\Order
     */
    public function changeOrderStatusAfterPosGenarated($order)
    {
        $strict = $this->_hlp->getScopeFlag('udropship/vendor/strict_change_order_status_after_po');
        $cosAfterPoStatus = $this->_hlp->getScopeConfig('udropship/vendor/change_order_status_after_po');
        $madStatuses = explode(',', $this->_hlp->getScopeConfig('udropship/vendor/make_available_to_dropship', $order->getStoreId()));
        if ($cosAfterPoStatus
            && (in_array($order->getStatus(), $madStatuses) || !$strict)
            && $order->getStatus()!=$cosAfterPoStatus
        ) {
            $states = $order->getConfig()->getStates();
            $state = $order->getState();
            foreach ($states as $__state=>$__stateLbl) {
                $stateStatuses = $order->getConfig()->getStateStatuses($__state, false);
                if (in_array($cosAfterPoStatus, $stateStatuses)) {
                    $state = $__state;
                    break;
                }
            }
            $order->setState($state)->addStatusHistoryComment(
                __('Order status changed after POs generated'),
                $cosAfterPoStatus);
        }
    }

    public function getRuntimeProductAttributesConfigPaths()
    {
        $paths = array();
        if (($pathsNode = $this->_hlp->getScopeConfig('global/udropship/runtime_product_attributes_config_paths'))) {
            $paths = $pathsNode->asArray();
            $paths = array_keys($paths);
        }
        return $paths;
    }



    public function dummy() {}

}