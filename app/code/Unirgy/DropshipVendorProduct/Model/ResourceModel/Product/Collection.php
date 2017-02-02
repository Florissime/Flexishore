<?php

namespace Unirgy\DropshipVendorProduct\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\DB\Select;

class Collection extends ProductCollection
{
    protected function _getSelectCountSql($select = null, $resetLeftJoins = true)
    {
        $this->_renderFilters();
        $countSelect = (is_null($select)) ?
            $this->_getClearSelect() :
            $this->_buildClearSelect($select);
        if ($this->getFlag('has_group_entity')) {
            $group = $countSelect->getPart(Select::GROUP);
            $newGroup = [];
            foreach ($group as $g) {
                if ("$g"!='e.entity_id') {
                    $newGroup[] = $g;
                }
            }
            $countSelect->setPart(Select::GROUP, $newGroup);
        }
        $countSelect->columns('COUNT(DISTINCT e.entity_id)');
        if ($resetLeftJoins) {
            $countSelect->resetJoinLeft();
        }
        return $countSelect;
    }
}