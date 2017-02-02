<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CatalogCategoryFlatLoadnodesBefore extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        if ($this->_msHlp->useVendorCategoriesFilter()) {
            $table = $this->_hlp->rHlp()->getTableName('catalog_category_entity');
            $table = 'main_table';
            if (($enableCatIds = $this->_hlp->getVendorEnableCategories())) {
                $a = $observer->getSelect()->getAdapter();
                $result = $a->quoteInto($table.'.entity_id in (?)', $enableCatIds);
                foreach ($enableCatIds as $enableCatId) {
                    $result .= ' OR '.$table.'.path like "/'.intval($enableCatId).'/"';
                }
            }
            if (($disableCatIds = $this->_hlp->getVendorDisableCategories())) {
                $a = $observer->getSelect()->getAdapter();
                $result = $a->quoteInto($table.'.entity_id not in (?)', $disableCatIds);
                foreach ($disableCatIds as $disableCatId) {
                    $result .= ' AND '.$table.'.path not like "/'.intval($disableCatId).'/"';
                }
            }
            $observer->getSelect()->where($result);
        }
    }
}
