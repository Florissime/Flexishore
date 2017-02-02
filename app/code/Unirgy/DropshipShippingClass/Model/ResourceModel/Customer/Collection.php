<?php

namespace Unirgy\DropshipShippingClass\Model\ResourceModel\Customer;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Psr\Log\LoggerInterface;
use Unirgy\Dropship\Helper\Data as HelperData;

class Collection extends AbstractCollection
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var RegionFactory
     */
    protected $_modelRegionFactory;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        HelperData $helperData,
        RegionFactory $modelRegionFactory,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        $this->_helperData = $helperData;
        $this->_modelRegionFactory = $modelRegionFactory;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    public function _construct()
    {
        $this->_init('Unirgy\DropshipShippingClass\Model\Customer',
                     'Unirgy\DropshipShippingClass\Model\ResourceModel\Customer');
    }

    public function toOptionArray()
    {
        $options = $this->_toOptionArray('class_id', 'class_name');
        foreach ($options as &$opt) {
            $opt['value'] = (string)$opt['value'];
        }
        unset($opt);
        return $options;
    }

    public function toOptionHash()
    {
        return $this->_toOptionHash('class_id', 'class_name');
    }

    public function addSortOrder()
    {
        $this->_select->order('sort_order ASC');
        return $this;
    }

    protected function _afterLoad()
    {
        $items = $this->getColumnValues('class_id');
        if (!count($items)) {
            parent::_afterLoad();
            return;
        }

        $conn = $this->getConnection();

        $table = $this->getTable('udshipclass_customer_row');
        $select = $conn->select()->from($table)->where($table . '.class_id IN (?)', $items);
        if ($result = $conn->fetchAll($select)) {
            $regionData = $regionIds = [];
            if ($this->getFlag('load_region_labels')) {
                foreach ($result as $row) {
                    $regionIds = array_unique(array_merge($regionIds, explode(',', $row['region_id'])));
                }
                if (!empty($regionIds)) {
                    $rFilterKey = 'main_table.region_id';
                    $regionCollection = $this->_modelRegionFactory->create()->getCollection()
                        ->addFieldToFilter($rFilterKey, ['in' => $regionIds]);
                    foreach ($regionCollection as $reg) {
                        $regionData[$reg->getId()] = $reg->getData();
                    }
                }
            }
            foreach ($result as $row) {
                $item = $this->getItemById($row['class_id']);
                if (!$item) continue;
                $rows = $item->getRows();
                if (!$rows) $rows = [];
                $row['region_data'] = array_intersect_key($regionData, array_flip(explode(',', $row['region_id'])));
                $regionNames = $regionCodes = [];
                foreach ($row['region_data'] as $rd) {
                    $regionNames[$rd['region_id']] = $rd['name'];
                    $regionCodes[$rd['region_id']] = $rd['code'];
                }
                $row['region_names'] = $regionNames;
                $row['region_codes'] = $regionCodes;
                $rows[] = $row;
                $item->setRows($rows);
            }
        }

        parent::_afterLoad();
    }
}
