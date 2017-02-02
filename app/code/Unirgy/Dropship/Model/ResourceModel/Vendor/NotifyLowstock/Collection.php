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

namespace Unirgy\Dropship\Model\ResourceModel\Vendor\NotifyLowstock;

use \Magento\CatalogInventory\Model\Stock;
use \Magento\Catalog\Model\Product\Attribute\Source\Status;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Db\Select;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        HelperData $helper,
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->_hlp = $helper;

        parent::__construct($entityFactory,$logger,$fetchStrategy,$eventManager,$eavConfig,$resource,$eavEntityFactory,$resourceHelper,$universalFactory,$storeManager,$moduleManager,$catalogProductFlatState,$scopeConfig,$productOptionFactory,$catalogUrl,$localeDate,$customerSession,$dateTime,$groupManagement,$connection);
    }

    protected $_vendor;
    public function setVendor($vendor)
    {
        $this->_vendor = $vendor;
        return $this;
    }
    public function getVendor()
    {
        return $this->_vendor;
    }
    public function initLowstockSelect($vendor)
    {
        $this->setVendor($vendor);
        $this->_initLowstockSelect();
        $this->addAttributeToFilter('status', array('in'=>array(Status::STATUS_ENABLED)));
        return $this;
    }
    protected function _initLowstockSelect()
    {
        $conn = $this->getResource()->getConnection();
        $this
            ->addAttributeToFilter('type_id', 'simple')
            ->addAttributeToSelect(array('sku', 'name'));
        $this->getSelect()->join(
            array('uv' => $this->getTable('udropship_vendor')),
            $conn->quoteInto('vendor_id=?', $this->getVendor()->getId()), 
            array('notify_lowstock_qty')
        );
        $this->getSelect()->joinLeft(
            array('uvls' => $this->getTable('udropship_vendor_lowstock')),
            'uvls.product_id=e.entity_id and uv.vendor_id=uvls.vendor_id',
            array('notified'=>'notified')
        );
        $this->getSelect()->where('uvls.notified IS NULL OR uvls.notified!=1');
        $this->_addAttributeJoin('udropship_vendor', 'left');
        $this->getSelect()->join(
            array('cisi' => $this->getTable('cataloginventory_stock_item')),
            $conn->quoteInto('cisi.product_id=e.entity_id AND cisi.stock_id=?',Stock::DEFAULT_STOCK_ID), 
            array('stock_status'=>$this->_getStockField('status'))
        );
        $this->getSelect()->joinLeft(
            array('uvp' => $this->getTable('udropship_vendor_product')),
            $conn->quoteInto('uvp.product_id=e.entity_id AND uvp.vendor_id=?', $this->getVendor()->getId()), 
            array('vendor_cost'=>'vendor_cost')
        );
        $vsAttr = $this->_hlp->getScopeConfig('udropship/vendor/vendor_sku_attribute');
        if (!$this->_hlp->isUdmultiAvailable()) {
            if ($vsAttr && $vsAttr!='sku' && $this->_hlp->checkProductAttribute($vsAttr)) {
                $this->addAttributeToSelect(array($vsAttr));
            }
        } else {
            $this->getSelect()->columns(array('vendor_sku'=>'uvp.vendor_sku'));
        }
        $this->getSelect()->columns(array('stock_qty'=>$this->_getStockField('qty')));
        
        if (!$this->_isLocalVendor() || !$this->_isLocalStock()) {
            $this->addAttributeToFilter('entity_id', array('in'=>$this->getVendor()->getAssociatedProductIds()));
        }
        if (!$this->_isLocalVendor() && $this->_isLocalStock()) {
            $this->getSelect()->where($conn->quoteInto(
                sprintf('%1$s is NULL OR %1$s!=?', $this->_getAttributeFieldName('udropship_vendor')),
                $this->getVendor()->getId()
            ));
        }
        $this->getSelect()
            ->where(sprintf(
            	'uvp.vendor_product_id is not null'
                .' AND ('
            	." uvp.stock_qty is not null AND uvp.stock_qty<=uv.notify_lowstock_qty"
            	." OR uv.vendor_id=%1\$s AND uvp.stock_qty is null AND cisi.qty<=uv.notify_lowstock_qty AND (%2\$s is NULL OR %2\$s!=%1\$s OR %3\$s)"
            	.') OR uvp.vendor_product_id is null'
                ." AND cisi.qty<=uv.notify_lowstock_qty", 
                $this->_hlp->getLocalVendorId(), $this->_getAttributeFieldName('udropship_vendor'), $this->_isLocalStock()
             ))
         ;
         return $this;
    }
    
    public function markLowstockNotified()
    {
        $conn = $this->getResource()->getConnection();
        $select = clone $this->getSelect();
        $select->reset(Select::COLUMNS);
        $select->columns($columns = array(
            'vendor_id' => new \Zend_Db_Expr($this->getVendor()->getId()),
            'product_id' => $this->_getAttributeFieldName('entity_id'),
            'notified_at' => new \Zend_Db_Expr($conn->quote($this->_hlp->now())),
            'notified' => new \Zend_Db_Expr(1)
        ));
        $this->_hlp->getObj('\Unirgy\Dropship\Model\ResourceModel\Vendor\NotifyLowstock')->markLowstockNotified($select, $columns);
    }
    
    protected function _isLocalStock()
    {
        return intval($this->_hlp->getScopeConfig('udropship/stock/availability')=='local_if_in_stock');
    }
    
    protected function _isLocalVendor()
    {
        return intval($this->getVendor()->getId()==$this->_hlp->getScopeConfig('udropship/vendor/local_vendor'));
    }
    
    protected function _getStockField($type)
    {
        if ($this->_hlp->isUdmultiAvailable()) {
            switch ($type) {
                case 'qty':
                    return new \Zend_Db_Expr('IF(uvp.vendor_product_id is null or ('.$this->_isLocalVendor().' and uvp.stock_qty is null), cisi.qty, uvp.stock_qty)');
                case 'status':
                    return new \Zend_Db_Expr('IF(uvp.vendor_product_id is null or '.$this->_isLocalVendor().', cisi.is_in_stock, null)');
            }
        } else {
            switch ($type) {
                case 'qty':
                    return 'cisi.qty';
                case 'status':
                    return 'cisi.is_in_stock';
            }
        }
    }
}
