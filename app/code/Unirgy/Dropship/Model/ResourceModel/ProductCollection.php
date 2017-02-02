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
 * @package    Unirgy_DropshipPo
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\Dropship\Model\ResourceModel;

class ProductCollection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    protected function _getSelectCountSql($select = null, $resetLeftJoins = true)
    {
        $this->_renderFilters();
        $countSelect = (is_null($select)) ?
            $this->_getClearSelect() :
            $this->_buildClearSelect($select);
        if ($this->getFlag('has_group_entity')) {
            $group = $countSelect->getPart(\Zend_Db_Select::GROUP);
            $newGroup = array();
            foreach ($group as $g) {
                if ("$g"!='e.entity_id') {
                    $newGroup[] = $g;
                }
            }
            $countSelect->setPart(\Zend_Db_Select::GROUP, $newGroup);
        }
        $countSelect->columns('COUNT(DISTINCT e.entity_id)');
        if ($resetLeftJoins) {
            $countSelect->resetJoinLeft();
        }
        $catHlp = \Magento\Framework\App\ObjectManager::getInstance()->get('\Unirgy\Dropship\Helper\Catalog');
        $catHlp->removePriceIndexFromProductColleciton($this, $countSelect);
        return $countSelect;
    }
}