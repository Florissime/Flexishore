<?php

namespace Unirgy\DropshipMicrosite\Model\Catalog;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Layer as ModelLayer;
use Magento\Catalog\Model\Layer\ContextInterface;
use Magento\Catalog\Model\Layer\StateFactory;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipMicrosite\Helper\Data as HelperData;

class Layer extends ModelLayer
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    public function __construct(ContextInterface $context, 
        StateFactory $layerStateFactory, 
        CollectionFactory $attributeCollectionFactory, 
        Product $catalogProduct, 
        StoreManagerInterface $storeManager, 
        Registry $registry, 
        CategoryRepositoryInterface $categoryRepository, 
        HelperData $helperData, 
        array $data = [])
    {
        $this->_helperData = $helperData;

        parent::__construct($context, $layerStateFactory, $attributeCollectionFactory, $catalogProduct, $storeManager, $registry, $categoryRepository, $data);
    }

    public function prepareProductCollection($collection)
    {
        parent::prepareProductCollection($collection);

        $this->_helperData->addVendorFilterToProductCollection($collection);

        return $this;
    }
    public $udApplied=false;
    public function apply()
    {
        $this->udApplied=true;
        return parent::apply();
    }
}