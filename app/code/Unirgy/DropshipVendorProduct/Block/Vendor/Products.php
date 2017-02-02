<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor;

use Magento\CatalogInventory\Model\Stock;
use Magento\CatalogInventory\Model\StockFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection as SetCollection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Model\App;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorProduct\Model\ResourceModel\Product\Collection;
use Unirgy\Dropship\Helper\Data as HelperData;

class Products extends Template
{
    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var Visibility
     */
    protected $_productVisibility;

    /**
     * @var StockFactory
     */
    protected $_modelStockFactory;

    /**
     * @var SetCollection
     */
    protected $_setCollection;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    public function __construct(Context $context, 
        Registry $frameworkRegistry, 
        HelperData $udropshipHelper,
        Visibility $productVisibility,
        StockFactory $modelStockFactory, 
        SetCollection $setCollection, 
        ProductFactory $modelProductFactory, 
        array $data = [])
    {
        $this->_registry = $frameworkRegistry;
        $this->_hlp = $udropshipHelper;
        $this->_productVisibility = $productVisibility;
        $this->_modelStockFactory = $modelStockFactory;
        $this->_setCollection = $setCollection;
        $this->_productFactory = $modelProductFactory;

        parent::__construct($context, $data);
    }

    protected $_collection;
    protected $_oldStoreId;
    protected $_unregUrlStore;

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        if (!$this->_registry->registry('url_store')) {
            $this->_unregUrlStore = true;
            $this->_registry->register('url_store', $this->_storeManager->getStore());
        }
        $this->_oldStoreId = $this->_storeManager->getStore()->getId();
        $this->_storeManager->setCurrentStore(0);

        if ($toolbar = $this->getLayout()->getBlock('udprod.grid.toolbar')) {
            $toolbar->setCollection($this->getProductCollection());
            $this->setChild('toolbar', $toolbar);
        }

        foreach ($this->getProductCollection() as $p) {
            if (!$this->_hlp->isUdmultiAvailable()) {
                if (($vsAttrCode = $this->_scopeConfig->getValue('udropship/vendor/vendor_sku_attribute', ScopeInterface::SCOPE_STORE)) && $this->_hlp->checkProductAttribute($vsAttrCode)) {
                    $p->setVendorSku($p->getData($vsAttrCode));
                }
            }
        }

        return $this;
    }

    protected function _getUrlModelClass()
    {
        return 'core/url';
    }
    public function getUrl($route = '', $params = [])
    {
        if (!isset($params['_store']) && $this->_oldStoreId) {
            $params['_store'] = $this->_oldStoreId;
        }
        return parent::getUrl($route, $params);
    }

    protected function _afterToHtml($html)
    {
        if ($this->_unregUrlStore) {
            $this->_unregUrlStore = false;
            $this->_registry->unregister('url_store');
        }
        $this->_storeManager->setCurrentStore($this->_oldStoreId);
        return parent::_afterToHtml($html);
    }

    protected function _applyRequestFilters($collection)
    {
        $r = $this->_request;
        $param = $r->getParam('filter_sku');
        if (!is_null($param) && $param!=='') {
            $collection->addAttributeToFilter('sku', ['like'=>$param.'%']);
        }
        $param = $r->getParam('filter_vendor_sku');
        if (!is_null($param) && $param!=='') {
            $vsAttrCode = $this->_scopeConfig->getValue('udropship/vendor/vendor_sku_attribute', ScopeInterface::SCOPE_STORE);
            if ($this->_hlp->isUdmultiAvailable()) {
                $collection->getSelect()->where('uvp.vendor_sku like ?', $param.'%');
            } elseif ($vsAttrCode && $this->_hlp->checkProductAttribute($vsAttrCode)) {
                $collection->addAttributeToFilter($vsAttrCode, ['like'=>$param.'%']);
            }
        }
        $param = $r->getParam('filter_name');
        if (!is_null($param) && $param!=='') {
            $collection->addAttributeToFilter('name', ['like'=>'%'.$param.'%']);
        }
        $param = $r->getParam('filter_system_status');
        if (!is_null($param) && $param!=='') {
            $collection->addAttributeToFilter('status', $param);
        }
        $param = $r->getParam('filter_stock_status');
        if (!is_null($param) && $param!=='') {
            $collection->getSelect()->where($this->_getStockField('status').'=?', $param);
        }
        $param = $r->getParam('filter_stock_qty_from');
        if (!is_null($param) && $param!=='') {
            //$collection->addAttributeToFilter('_stock_qty', array('gteq'=>$param));
            $collection->getSelect()->where($this->_getStockField('qty').'>=?', $param);
        }
        $param = $r->getParam('filter_stock_qty_to');
        if (!is_null($param) && $param!=='') {
            //$collection->addAttributeToFilter('_stock_qty', array('lteq'=>$param));
            $collection->getSelect()->where($this->_getStockField('qty').'<=?', $param);
        }
        $param = $r->getParam('filter_price_from');
        if (!is_null($param) && $param!=='') {
            $collection->addAttributeToFilter('price', ['gteq'=>$param]);
        }
        $param = $r->getParam('filter_price_to');
        if (!is_null($param) && $param!=='') {
            $collection->addAttributeToFilter('price', ['lteq'=>$param]);
        }
        return $this;
    }

    protected function _getStockField($type)
    {
        $v = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendor();
        if (!$v || !$v->getId()) {
            $isLocalVendor = 0;
        } else {
            $isLocalVendor = intval($v->getId()==$this->_scopeConfig->getValue('udropship/vendor/local_vendor', ScopeInterface::SCOPE_STORE));
        }
        if ($this->_hlp->isUdmultiActive()) {
            switch ($type) {
                case 'qty':
                    return new \Zend_Db_Expr('IF(uvp.vendor_product_id is null, cisi.qty, uvp.stock_qty)');
                case 'status':
                    return new \Zend_Db_Expr("IF(uvp.vendor_product_id is null or $isLocalVendor, cisi.is_in_stock, null)");
            }
        } else {
            switch ($type) {
                case 'qty':
                    return 'ciss.qty';
                case 'status':
                    return 'ciss.stock_status';
            }
        }
    }

    public function getProductCollection()
    {
        if (!$this->_collection) {
            $v = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendor();
            if (!$v || !$v->getId()) {
                return [];
            }
            $r = $this->_request;
            $res = $this->_hlp->rHlp();
            #$read = $res->getConnection('catalog_product');
            $stockTable = $res->getTableName('cataloginventory_stock_item');
            $stockStatusTable = $res->getTableName('cataloginventory_stock_status');
            $wId = (int)$this->_storeManager->getDefaultStoreView()->getWebsiteId();
            if ($this->_hlp->hasMageFeature('stock_website')) {
                $wId = 0;
            }
            $collection = $this->_hlp->createObj('\Unirgy\Dropship\Model\ResourceModel\ProductCollection')
                ->setFlag('udskip_price_index',1)
                ->setFlag('has_group_entity', 1)
                ->setFlag('has_stock_status_filter', 1)
                ->addAttributeToFilter('type_id', ['in'=>['simple','configurable','downloadable','virtual']])
                ->addAttributeToSelect(['sku', 'name', 'status', 'price'])
            ;
            $collection->addAttributeToFilter('entity_id', ['in'=>$v->getAssociatedProductIds()]);
            $collection->addAttributeToFilter('visibility', ['in'=>$this->_productVisibility->getVisibleInSiteIds()]);
            $conn = $collection->getConnection();
            $wIdsSql = $conn->quote([$wId]);
            //$collection->addAttributeToFilter('entity_id', array('in'=>array_keys($v->getAssociatedProducts())));
            $collection->getSelect()
                ->join(
                ['cisi' => $stockTable],
                $conn->quoteInto('cisi.product_id=e.entity_id AND cisi.stock_id=?', Stock::DEFAULT_STOCK_ID),
                    []
                )
                ->joinLeft(
                    ['ciss' => $stockStatusTable],
                    $conn->quoteInto('ciss.product_id=e.entity_id AND ciss.website_id in ('.$wIdsSql.') AND ciss.stock_id=?', Stock::DEFAULT_STOCK_ID),
                ['_stock_status'=>$this->_getStockField('status')]
            );
            if ($this->_hlp->isUdmultiAvailable()) {
                $collection->getSelect()->joinLeft(
                    ['uvp' => $res->getTableName('udropship_vendor_product')],
                    $conn->quoteInto('uvp.product_id=e.entity_id AND uvp.vendor_id=?', $v->getId()),
                    ['_stock_qty'=>$this->_getStockField('qty'), 'vendor_sku'=>'uvp.vendor_sku', 'vendor_cost'=>'uvp.vendor_cost']
                );
                //$collection->getSelect()->columns(array('_stock_qty'=>'IFNULL(uvp.stock_qty,cisi.qty'));
            } else {
                if (($vsAttrCode = $this->_scopeConfig->getValue('udropship/vendor/vendor_sku_attribute', ScopeInterface::SCOPE_STORE)) && $this->_hlp->checkProductAttribute($vsAttrCode)) {
                    $collection->addAttributeToSelect([$vsAttrCode]);
                }
                $collection->getSelect()->columns(['_stock_qty'=>$this->_getStockField('qty')]);
            }
$collection->addAttributeToFilter('udropship_vendor', $v->getId());

            $this->_applyRequestFilters($collection);

            $collection->getSelect()->order('e.entity_id desc');
            $collection->getSelect()->group('e.entity_id');
            $collection->getSize();

            #$this->_modelStockFactory->create()->addItemsToProducts($collection);
            $this->_collection = $collection;
        }
        return $this->_collection;
    }
    public function getSetIdSelectHtml()
    {
        $options = $this->_setCollection
            ->setEntityTypeFilter($this->_productFactory->create()->getResource()->getEntityType()->getId())
            ->load()
            ->toOptionArray();
        array_unshift($options, ['value'=>'','label'=>'* Please select']);
        return $this->getLayout()->createBlock('Magento\Framework\View\Element\Html\Select')
            ->setName('set_id')
            ->setId('set_id')
            ->setTitle(__('Attribute Set'))
            ->setClass('validate-select absolute-advice')
            ->setOptions($options)->toHtml();
    }
}