<?php

namespace Unirgy\DropshipShippingClass\Model\ResourceModel;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\ResourceModel\Helper as DbHelper;

class Customer extends AbstractDb
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var RegionFactory
     */
    protected $_modelRegionFactory;

    /**
     * @var DbHelper
     */
    protected $_dbHelper;

    public function __construct(
        Context $context,
        HelperData $helperData,
        RegionFactory $modelRegionFactory,
        DbHelper $dbHelper
    ) {
        $this->_helperData = $helperData;
        $this->_modelRegionFactory = $modelRegionFactory;
        $this->_dbHelper = $dbHelper;

        parent::__construct($context);
    }

    public function _construct()
    {
        $this->_init('udshipclass_customer', 'class_id');
    }

    protected function _initUniqueFields()
    {
        $this->_uniqueFields = [
            [
                'field' => ['class_name'],
                'title' => __('An error occurred while saving this ship class. A class with the same name already exists.'),
            ]
        ];
        return $this;
    }

    protected function _afterLoad(AbstractModel $object)
    {
        if (!$object->getId()) {
            return parent::_afterLoad($object);
        }

        $conn = $this->_getConnection('read');

        $table = $this->getTable('udshipclass_customer_row');
        $select = $conn->select()->from($table)->where($table . '.class_id IN (?)', $object->getId());
        if ($result = $conn->fetchAll($select)) {
            $regionData = $regionIds = [];
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
            foreach ($result as $row) {
                $rows = $object->getRows();
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
                $object->setRows($rows);
            }
        }

        return parent::_afterLoad($object);
    }

    protected function _afterSave(AbstractModel $object)
    {
        $conn = $this->_getConnection('write');
        $conn->delete(
            $this->getTable('udshipclass_customer_row'),
            $conn->quoteInto('class_id=?', $object->getId())
        );
        if (($rows = $object->getRows()) && is_array($rows)) {
            unset($rows['$ROW']);
            foreach ($rows as &$row) {
                if (empty($row['region_id'])) {
                    $row['region_id'] = '';
                }
                if (is_array($row['region_id'])) {
                    $row['region_id'] = implode(',', $row['region_id']);
                }
                $row['class_id'] = $object->getId();
            }
            unset($row);
            if (!empty($rows)) {
                $this->_dbHelper->multiInsertOnDuplicate(
                    $this->getTable('udshipclass_customer_row'), $rows, ['postcode', 'region_id']
                );
            }
        }
    }
}
