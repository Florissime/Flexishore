<?php

namespace Unirgy\Dropship\Model\ResourceModel\Po;

use \Magento\Eav\Model\Config;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\DB\Adapter\AdapterInterface;
use \Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use \Magento\Framework\Data\Collection\EntityFactoryInterface;
use \Magento\Framework\Event\ManagerInterface;
use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use \Magento\Sales\Model\Order;
use \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection as ShipmentCollection;
use \Psr\Log\LoggerInterface;
use \Unirgy\Dropship\Helper\Catalog;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Collection extends ShipmentCollection
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    protected $_eavConfig;

    /**
     * @var Catalog
     */
    protected $_helperCatalog;


    public function __construct(
        HelperData $helperData,
        ScopeConfigInterface $scopeConfig,
        Config $eavConfig,
        Catalog $helperCatalog,
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger, 
        FetchStrategyInterface $fetchStrategy, 
        ManagerInterface $eventManager, 
        Snapshot $entitySnapshot, 
        AdapterInterface $connection = null, 
        AbstractDb $resource = null
    )
    {
        $this->_hlp = $helperData;
        $this->scopeConfig = $scopeConfig;
        $this->_eavConfig = $eavConfig;
        $this->_helperCatalog = $helperCatalog;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $entitySnapshot, $connection, $resource);
    }

    protected function _construct()
    {
        $this->_init('Unirgy\Dropship\Model\Po', 'Unirgy\Dropship\Model\ResourceModel\Po');
    }

    public function addPendingBatchStatusFilter()
    {
        return $this->_addPendingBatchStatusFilter();
    }
    public function addPendingBatchStatusVendorFilter($vendor)
    {
        return $this->_addPendingBatchStatusFilter($vendor);
    }
    protected function _addPendingBatchStatusFilter($vendor=null)
    {
        if (is_array($vendor)) {
            $exportOnPoStatusAll = array();
            foreach ($vendor as $vId) {
                $exportOnPoStatus = array();
                if ($vId && ($v = $this->_hlp->getVendor($vId)) && $v->getId()) {
                    $exportOnPoStatus = $v->getData('batch_export_orders_export_on_po_status');
                }
                if (in_array('999', $exportOnPoStatus) || empty($exportOnPoStatus)) {
                    $exportOnPoStatus = $this->_hlp->getScopeConfig('udropship/batch/export_on_po_status');
                    if (!is_array($exportOnPoStatus)) {
                        $exportOnPoStatus = explode(',', $exportOnPoStatus);
                    }
                }
                $exportOnPoStatusAll[(int)$vId] = $this->getSelect()->getAdapter()->quoteInto('udropship_status in (?)', $exportOnPoStatus);
            }
        } else {
            $exportOnPoStatus = array();
            if ($vendor && ($vendor = $this->_hlp->getVendor($vendor)) && $vendor->getId()) {
                $exportOnPoStatus = $vendor->getData('batch_export_orders_export_on_po_status');
            }
            if (in_array('999', $exportOnPoStatus) || empty($exportOnPoStatus)) {
                $exportOnPoStatus = $this->_hlp->getScopeConfig('udropship/batch/export_on_po_status');
                if (!is_array($exportOnPoStatus)) {
                    $exportOnPoStatus = explode(',', $exportOnPoStatus);
                }
            }
        }
        $this->getSelect()->where("main_table.udropship_status in (?)", $exportOnPoStatus);
        return $this;
    }

    public function addOrders()
    {
        $orderIds = array();
        foreach ($this as $po) {
            if ($po->getOrderId()) {
                $orderIds[$po->getOrderId()] = 1;
            }
        }

        if ($orderIds) {
            $orders = $this->_hlp->createObj('\Magento\Sales\Model\Order')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in'=>array_keys($orderIds)));
            foreach ($this as $po) {
                $po->setOrder($orders->getItemById($po->getOrderId()));
            }
        }
        return $this;
    }
    protected function _afterLoad()
    {
        parent::_afterLoad();
        $this->walk('unserializeFields');
        return $this;
    }
}