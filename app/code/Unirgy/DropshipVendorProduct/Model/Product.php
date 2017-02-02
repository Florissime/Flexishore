<?php

namespace Unirgy\DropshipVendorProduct\Model;

use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductLinkExtensionFactory;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Helper\Product as HelperProduct;
use Magento\Catalog\Model\Indexer\Product\Eav\Processor as EavProcessor;
use Magento\Catalog\Model\Indexer\Product\Flat\Processor;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceProcessor;
use Magento\Catalog\Model\Product as ModelProduct;
use Magento\Catalog\Model\ProductLink\CollectionProvider;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\EntryConverterPool;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Configuration\Item\OptionFactory;
use Magento\Catalog\Model\Product\Image\CacheFactory;
use Magento\Catalog\Model\Product\Link;
use Magento\Catalog\Model\Product\LinkTypeProvider;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\Product\OptionFactory as ProductOptionFactory;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Url;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product as ResourceModelProduct;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\Context;
use Magento\Framework\Module\Manager;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Product extends ModelProduct
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    public function __construct(Context $context, 
        Registry $registry, 
        ExtensionAttributesFactory $extensionFactory, 
        AttributeValueFactory $customAttributeFactory, 
        StoreManagerInterface $storeManager, 
        ProductAttributeRepositoryInterface $metadataService, 
        Url $url, 
        Link $productLink, 
        OptionFactory $itemOptionFactory, 
        StockItemInterfaceFactory $stockItemFactory, 
        ProductOptionFactory $catalogProductOptionFactory, 
        Visibility $catalogProductVisibility, 
        Status $catalogProductStatus, 
        Config $catalogProductMediaConfig, 
        Type $catalogProductType, 
        Manager $moduleManager, 
        HelperProduct $catalogProduct, 
        \Unirgy\DropshipVendorProduct\Model\ResourceModel\Product $resource,
        \Unirgy\DropshipVendorProduct\Model\ResourceModel\Product\Collection $resourceCollection,
        CollectionFactory $collectionFactory,
        Filesystem $filesystem, 
        IndexerRegistry $indexerRegistry, 
        Processor $productFlatIndexerProcessor, 
        PriceProcessor $productPriceIndexerProcessor, 
        EavProcessor $productEavIndexerProcessor, 
        CategoryRepositoryInterface $categoryRepository, 
        CacheFactory $imageCacheFactory, 
        CollectionProvider $entityCollectionProvider, 
        LinkTypeProvider $linkTypeProvider, 
        ProductLinkInterfaceFactory $productLinkFactory, 
        ProductLinkExtensionFactory $productLinkExtensionFactory, 
        EntryConverterPool $mediaGalleryEntryConverterPool, 
        DataObjectHelper $dataObjectHelper, 
        JoinProcessorInterface $joinProcessor, 
        ScopeConfigInterface $configScopeConfigInterface, 
        array $data = [])
    {
        $this->_scopeConfig = $configScopeConfigInterface;

        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $storeManager, $metadataService, $url, $productLink, $itemOptionFactory, $stockItemFactory, $catalogProductOptionFactory, $catalogProductVisibility, $catalogProductStatus, $catalogProductMediaConfig, $catalogProductType, $moduleManager, $catalogProduct, $resource, $resourceCollection, $collectionFactory, $filesystem, $indexerRegistry, $productFlatIndexerProcessor, $productPriceIndexerProcessor, $productEavIndexerProcessor, $categoryRepository, $imageCacheFactory, $entityCollectionProvider, $linkTypeProvider, $productLinkFactory, $productLinkExtensionFactory, $mediaGalleryEntryConverterPool, $dataObjectHelper, $joinProcessor, $data);
    }

    protected function _construct()
    {
        $this->_init('Unirgy\DropshipVendorProduct\Model\ResourceModel\Product');
    }
    public function resetTypeInstance()
    {
        $this->_typeInstanceSingleton = null;
        $this->_typeInstance = null;
        return $this;
    }
    public function beforeSave()
    {
        if ($this->getName() !== false) {
            if (!$this->_scopeConfig->isSetFlag('udprod/general/disable_name_check', ScopeInterface::SCOPE_STORE)) {
                $ufName = $this->formatUrlKey($this->getName());
                if (!trim($ufName)) {
                    throw new \Exception(__('Product name is invalid'));
                }
            }
        }
        return parent::beforeSave();
    }
    public function uclearOptions()
    {
        $this->getOptionInstance()->unsetOptions();
        $this->_options = [];
        return $this;
    }
}