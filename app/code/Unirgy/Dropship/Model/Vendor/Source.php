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

namespace Unirgy\Dropship\Model\Vendor;

use \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Db\Select;
use \Magento\Framework\Model\App;
use \Unirgy\Dropship\Helper\Catalog;
use \Unirgy\Dropship\Model\Source as ModelSource;

class Source extends AbstractSource
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ModelSource
     */
    protected $_src;

    /**
     * @var Catalog
     */
    protected $_helperCatalog;

    /**
     * @var \Unirgy\Dropship\Helper\Data
     */
    protected $_hlp;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Unirgy\Dropship\Model\Source $source,
        \Unirgy\Dropship\Helper\Data $helper,
        Catalog $helperCatalog
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->_hlp = $helper;
        $this->_src = $source;
        $this->_helperCatalog = $helperCatalog;

    }

    protected static $_isEnabled;
    protected function _isEnabled()
    {
        if (is_null(self::$_isEnabled)) {
            self::$_isEnabled = $this->_hlp->isModuleActive('Unirgy_Dropship');
        }
        return self::$_isEnabled;
    }

    public function getAllOptions($withEmpty = true, $defaultValues = false)
    {
        $options = $this->toOptionArray();
        if ($withEmpty) {
            array_unshift($options, array('label' => '', 'value' => ''));
        }
        return $options;
    }

    public function toOptionArray()
    {
        $source = $this->_getSource();
        return $source ? $source->toOptionArray() : array();
    }

    public function toOptionHash()
    {
        $source = $this->_getSource();
        return $source ? $source->toOptionHash() : array();
    }

    protected function _getSource()
    {
        if (!$this->_isEnabled()) {
            return false;
        }
        return $this->_src->setPath('vendors');
    }

    public function addValueSortToCollection($collection, $dir = Select::SQL_ASC)
    {
        $valueTable1    = $this->getAttribute()->getAttributeCode() . '_t1';
        $valueTable2    = $this->getAttribute()->getAttributeCode() . '_t2';
        $collection->getSelect()
            ->joinLeft(
                array($valueTable1 => $this->getAttribute()->getBackend()->getTable()),
                "e.entity_id={$valueTable1}.entity_id"
                . " AND {$valueTable1}.attribute_id='{$this->getAttribute()->getId()}'"
                . " AND {$valueTable1}.store_id=0",
                array())
            ->joinLeft(
                array($valueTable2 => $this->getAttribute()->getBackend()->getTable()),
                "e.entity_id={$valueTable2}.entity_id"
                . " AND {$valueTable2}.attribute_id='{$this->getAttribute()->getId()}'"
                . " AND {$valueTable2}.store_id='{$collection->getStoreId()}'",
                array()
            );
        $valueExpr = $this->_helperCatalog
            ->getCheckSql("{$valueTable2}.value_id > 0", "{$valueTable2}.value", "{$valueTable1}.value");

        /* @var \Unirgy\Dropship\Model\ResourceModel\Helper $rHlp */
        $rHlp = $this->_hlp->getObj('\Unirgy\Dropship\Model\ResourceModel\Helper');
        $conn = $rHlp->getConnection();

        $attributeCode  = $this->getAttribute()->getAttributeCode();
        $optionTable1   = $attributeCode . '_option_value_t1';
        $tableJoinCond1 = "{$optionTable1}.vendor_id={$valueExpr}";

        $collection->getSelect()
            ->joinLeft(
                array($optionTable1 => $rHlp->getTable('udropship_vendor')),
                $tableJoinCond1,
                array($attributeCode=>$valueExpr, $attributeCode.'_value' => $optionTable1.'.vendor_name'));

        $collection->getSelect()
            ->order("{$this->getAttribute()->getAttributeCode()}_value {$dir}");

        return $this;
    }

    public function getFlatColums()
    {
        $columns = array();
        $attributeCode = $this->getAttribute()->getAttributeCode();

        $columns[$attributeCode] = array(
            'type'      => 'int',
            'unsigned'  => false,
            'is_null'   => true,
            'default'   => null,
            'extra'     => null
        );
        $columns[$attributeCode . '_value'] = array(
            'type'      => 'varchar(255)',
            'unsigned'  => false,
            'is_null'   => true,
            'default'   => null,
            'extra'     => null
        );

        return $columns;
    }

    public function getFlatIndexes()
    {
        $indexes = array();

        $index = sprintf('IDX_%s', strtoupper($this->getAttribute()->getAttributeCode()));
        $indexes[$index] = array(
            'type'      => 'index',
            'fields'    => array($this->getAttribute()->getAttributeCode())
        );

        $sortable   = $this->getAttribute()->getUsedForSortBy();
        if ($sortable) {
            $index = sprintf('IDX_%s_VALUE', strtoupper($this->getAttribute()->getAttributeCode()));

            $indexes[$index] = array(
                'type'      => 'index',
                'fields'    => array($this->getAttribute()->getAttributeCode() . '_value')
            );
        }

        return $indexes;
    }
    
    public function getFlatUpdateSelect($store)
    {
        /* @var \Unirgy\Dropship\Model\ResourceModel\Helper $rHlp */
        $rHlp = $this->_hlp->getObj('\Unirgy\Dropship\Model\ResourceModel\Helper');
        $conn = $rHlp->getConnection();
        $attribute = $this->getAttribute();
        $adapter        = $conn;
        $attributeTable = $attribute->getBackend()->getTable();
        $attributeCode  = $attribute->getAttributeCode();

        $joinConditionTemplate = "%s.entity_id = %s.entity_id"
            . " AND %s.attribute_id = " . $attribute->getId()
            . " AND %s.store_id = %d";
        $joinCondition = sprintf($joinConditionTemplate, 'e', 't1', 't1', 't1', 't1', 0);

        $valueExpr = $this->_helperCatalog->getCheckSql('t2.value_id > 0', 't2.value', 't1.value');
        /** @var $select Select */
        $select = $adapter->select()
            ->joinLeft(array('t1' => $attributeTable), $joinCondition, array())
            ->joinLeft(array('t2' => $attributeTable),
                sprintf($joinConditionTemplate, 't1', 't2', 't2', 't2', 't2', $store),
                array($attributeCode => $valueExpr));

        $select
            ->joinLeft(array('to2' => $rHlp->getTable('udropship_vendor')),
                "to2.vendor_id = {$valueExpr}",
                array($attributeCode . '_value' => 'to2.vendor_name'));

        return $select;
    }
}