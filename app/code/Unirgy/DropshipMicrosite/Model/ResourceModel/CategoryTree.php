<?php

namespace Unirgy\DropshipMicrosite\Model\ResourceModel;

use Magento\Catalog\Model\Attribute\Config;
use Magento\Catalog\Model\ResourceModel\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection\Factory;
use Magento\Catalog\Model\ResourceModel\Category\Tree;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipMicrosite\Helper\Data as HelperData;

class CategoryTree extends Tree
{
    /**
     * @var HelperData
     */
    protected $_msHlp;

    public function __construct(Category $catalogCategory,
        CacheInterface $cache, 
        StoreManagerInterface $storeManager, 
        ResourceConnection $resource, 
        ManagerInterface $eventManager, 
        Config $attributeConfig, 
        Factory $collectionFactory,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        HelperData $helperData
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_msHlp = $helperData;

        parent::__construct($catalogCategory, $cache, $storeManager, $resource, $eventManager, $attributeConfig, $collectionFactory);
        if ($this->_msHlp->useVendorCategoriesFilter()) {
            $table = $this->_hlp->rHlp()->getTableName('catalog_category_entity');
            if (($enableCatIds = $this->_msHlp->getVendorEnableCategories())) {
                $a = $this->_select->getAdapter();
                $result = $a->quoteInto($table.'.entity_id in (?)', $enableCatIds);
                foreach ($enableCatIds as $enableCatId) {
                    $result .= ' OR '.$table.'.path like "/'.intval($enableCatId).'/"';
                }
            }
            if (($disableCatIds = $this->_msHlp->getVendorDisableCategories())) {
                $a = $this->_select->getAdapter();
                $result = $a->quoteInto($table.'.entity_id not in (?)', $disableCatIds);
                foreach ($disableCatIds as $disableCatId) {
                    $result .= ' AND '.$table.'.path not like "/'.intval($disableCatId).'/"';
                }
            }
            $this->_select->where($result);
        }
    }
}