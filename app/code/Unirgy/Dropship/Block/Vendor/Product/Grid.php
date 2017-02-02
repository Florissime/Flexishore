<?php

namespace Unirgy\Dropship\Block\Vendor\Product;

use \Magento\CatalogInventory\Model\Stock;
use \Magento\Catalog\Model\Product;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Store\Model\StoreManagerInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Grid extends Template
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(
        HelperData $helperData,
        Context $context,
        array $data = []
    )
    {
        $this->_hlp = $helperData;

        parent::__construct($context, $data);
    }

    protected $_collection;

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $oldStoreId = $this->_storeManager->getStore()->getId();
        $this->_oldStoreId = $this->_storeManager->getStore()->getId();
        $this->_storeManager->setCurrentStore(0);
        if ($toolbar = $this->getLayout()->getBlock('product.grid.toolbar')) {
            $toolbar->setCollection($this->getProductCollection());
            $this->setChild('toolbar', $toolbar);
        }
        $this->getProductCollection()->load();

        if ($this->_hlp->isModuleActive('Unirgy_DropshipSellYours')) {
            $findEditOfferIds = array();
            foreach ($this->getProductCollection() as $p) {
                if (!$p->isVisibleInSiteVisibility()) {
                    $findEditOfferIds[] = $p->getEntityId();
                    $p->setHasEditOfferId(1);
                }
            }
            if (!empty($findEditOfferIds)) {
                /** @var \Unirgy\Dropship\Model\ResourceModel\Helper $rHlp */
                $rHlp = $this->_hlp->getObj('\Unirgy\Dropship\Model\ResourceModel\Helper');
                $conn = $rHlp->getConnection();
                $findEditOffersSel = $conn->select()
                    ->from($rHlp->getTable('catalog_product_super_link'))
                    ->where('product_id in (?)', $findEditOfferIds);
                $findEditOffers = $conn->fetchAll(
                    $findEditOffersSel
                );
                if (is_array($findEditOffers)) {
                    foreach ($findEditOffers as $__feo) {
                        foreach ($this->getProductCollection() as $p) {
                            if ($__feo['product_id']==$p->getEntityId()) {
                                $p->setEditOfferId($__feo['parent_id']);
                                break;
                            }
                        }
                    }
                }
            }
        }


        foreach ($this->getProductCollection() as $p) {
            if (!$this->_hlp->isUdmultiAvailable()) {
                if (($vsAttrCode = $this->_scopeConfig->getValue('udropship/vendor/vendor_sku_attribute')) && $this->_hlp->checkProductAttribute($vsAttrCode)) {
                    $p->setVendorSku($p->getData($vsAttrCode));
                }
            }
        }

        $this->_storeManager->getStore()->setId($oldStoreId);
        $this->_storeManager->setCurrentStore($this->_oldStoreId);

        return $this;
    }
    
    protected function _applyRequestFilters($collection)
    {
        $r = $this->_request;
        $param = $r->getParam('filter_sku');
        if (!is_null($param) && $param!=='') {
            $collection->addAttributeToFilter('sku', array('like'=>'%'.$param.'%'));
        }
        $param = $r->getParam('filter_vendor_sku');
        if (!is_null($param) && $param!=='') {
            $vsAttrCode = $this->_scopeConfig->getValue('udropship/vendor/vendor_sku_attribute');
            if ($this->_hlp->isUdmultiAvailable()) {
                $collection->getSelect()->where('uvp.vendor_sku like ?', $param.'%');
            } elseif ($vsAttrCode && $this->_hlp->checkProductAttribute($vsAttrCode)) {
                $collection->addAttributeToFilter($vsAttrCode, array('like'=>$param.'%'));
            }
        }
        $param = $r->getParam('filter_name');
        if (!is_null($param) && $param!=='') {
            $collection->addAttributeToFilter('name', array('like'=>$param.'%'));
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
        return $this;
    }
    
    protected function _getStockField($type)
    {
        $v = $this->_hlp->session()->getVendor();
        if (!$v || !$v->getId()) {
            $isLocalVendor = 0;
        } else {
            $isLocalVendor = intval($v->getId()==$this->_scopeConfig->getValue('udropship/vendor/local_vendor'));
        }
        if ($this->_hlp->isUdmultiAvailable()) {
            switch ($type) {
                case 'is_qty':
                    return new \Zend_Db_Expr('1');
                case 'qty':
                    return new \Zend_Db_Expr('IF(uvp.vendor_product_id is null, cisi.qty, uvp.stock_qty)');
                case 'status':
                    return new \Zend_Db_Expr("IF(uvp.vendor_product_id is null, cisi.is_in_stock, null)");
            }
        } else {
            $isManageStock = $this->_hlp->getScopeFlag(\Magento\CatalogInventory\Model\Configuration::XML_PATH_MANAGE_STOCK);
            switch ($type) {
                case 'is_qty':
                    return new \Zend_Db_Expr(sprintf('IF (cisi.use_config_manage_stock && 0=%d || !cisi.use_config_manage_stock && 0=cisi.manage_stock, null, 1)', $isManageStock));
                case 'qty':
                    return 'cisi.qty';
                case 'status':
                    return new \Zend_Db_Expr(sprintf('IF (cisi.use_config_manage_stock && 0=%d || !cisi.use_config_manage_stock && 0=cisi.manage_stock, null, cisi.is_in_stock)', $isManageStock));
            }
        }
    }

    protected $_oldStoreId;
    public function getProductCollection()
    {
        if (!$this->_collection) {
            $v = $this->_hlp->session()->getVendor();
            if (!$v || !$v->getId()) {
                return array();
            }
            $r = $this->_request;
            $res = $this->_hlp->rHlp();
            $stockTable = $res->getTableName('cataloginventory_stock_item');
            $collection = $this->_hlp->createObj('\Unirgy\Dropship\Model\ResourceModel\ProductCollection')
                ->setFlag('udskip_price_index',1)
                ->setFlag('has_stock_status_filter', 1)
                //->addAttributeToFilter('udropship_vendor', $v->getId())
                ->addAttributeToFilter('type_id', array('in'=>array('simple','downloadable','virtual')))
                ->addAttributeToSelect(array('sku', 'name', 'visibility'/*, 'cost'*/))
            ;
            $conn = $collection->getConnection();
            $collection->addAttributeToFilter('entity_id', array('in'=>$v->getAssociatedProductIds()));
            $collection->getSelect()->join(
                array('cisi' => $stockTable), 
                $conn->quoteInto('cisi.product_id=e.entity_id AND cisi.stock_id=?', Stock::DEFAULT_STOCK_ID), 
                array('_stock_status'=>$this->_getStockField('status'), '_is_stock_qty'=>$this->_getStockField('is_qty'))
            );
            if ($this->_hlp->isUdmultiAvailable()) {
                $collection->getSelect()->join(
                    array('uvp' => $res->getTableName('udropship_vendor_product')),
                    $conn->quoteInto('uvp.product_id=e.entity_id AND uvp.vendor_id=?', $v->getId()), 
                    array('_stock_qty'=>$this->_getStockField('qty'), 'vendor_sku'=>'uvp.vendor_sku', 'vendor_cost'=>'uvp.vendor_cost')
                );
                //$collection->getSelect()->columns(array('_stock_qty'=>'IFNULL(uvp.stock_qty,cisi.qty'));
            } else {
                if (($vsAttrCode = $this->_scopeConfig->getValue('udropship/vendor/vendor_sku_attribute')) && $this->_hlp->checkProductAttribute($vsAttrCode)) {
                    $collection->addAttributeToSelect(array($vsAttrCode));
                }
                $collection->getSelect()->columns(array('_stock_qty'=>$this->_getStockField('qty')));
            }

            $this->_applyRequestFilters($collection);

            $this->_collection = $collection;
        }
        return $this->_collection;
    }
}