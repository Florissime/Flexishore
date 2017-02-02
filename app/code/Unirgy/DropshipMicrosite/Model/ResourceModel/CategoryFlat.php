<?php

namespace Unirgy\DropshipMicrosite\Model\ResourceModel;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\Flat;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\Table\StrategyInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipMicrosite\Helper\Data as HelperData;

class CategoryFlat extends Flat
{
    /**
     * @var HelperData
     */
    protected $msHlp;

    public function __construct(Context $context, 
        StrategyInterface $tableStrategy, 
        CategoryFactory $categoryFactory, 
        CollectionFactory $categoryCollectionFactory, 
        StoreManagerInterface $storeManager, 
        Config $catalogConfig, 
        ManagerInterface $eventManager, 
        HelperData $helperData)
    {
        $this->msHlp = $helperData;

        parent::__construct($context, $tableStrategy, $categoryFactory, $categoryCollectionFactory, $storeManager, $catalogConfig, $eventManager);
    }

    protected $_uNodes=[];
    protected $_uLoaded=[];
    public function getNodes($parentId, $recursionLevel = 0, $storeId = 0)
    {
        $uKey = '0';
        if ($this->msHlp->useVendorCategoriesFilter()) {
            $uKey = $this->msHlp->getCurrentVendor()->getId();
        }
        if (empty($this->_uLoaded[$uKey])) {
            $selectParent = $this->getConnection()->select()
                ->from($this->getMainStoreTable($storeId))
                ->where('entity_id = ?', $parentId);
            if ($parentNode = $this->getConnection()->fetchRow($selectParent)) {
                $parentNode['id'] = $parentNode['entity_id'];
                $parentNode = $this->_modelCategoryFactory->create()->setData($parentNode);
                $this->_uNodes[$uKey][$parentNode->getId()] = $parentNode;
                $nodes = $this->_loadNodes($parentNode, $recursionLevel, $storeId);
                $childrenItems = [];
                foreach ($nodes as $node) {
                    $pathToParent = explode('/', $node->getPath());
                    array_pop($pathToParent);
                    $pathToParent = implode('/', $pathToParent);
                    $childrenItems[$pathToParent][] = $node;
                }
                $this->addChildNodes($childrenItems, $parentNode->getPath(), $parentNode);
                $childrenNodes = $this->_uNodes[$uKey][$parentNode->getId()];
                if ($childrenNodes->getChildrenNodes()) {
                    $this->_uNodes[$uKey] = $childrenNodes->getChildrenNodes();
                }
                else {
                    $this->_uNodes[$uKey] = [];
                }
                $this->_uLoaded[$uKey] = true;
            }
        }
        return $this->_uNodes[$uKey];
    }
}
