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

namespace Unirgy\Dropship\Block\Adminhtml\ReportItem;

use \Magento\Backend\Block\Template\Context;
use \Magento\Backend\Block\Widget\Grid as WidgetGrid;
use \Magento\Backend\Helper\Data as HelperData;
use \Magento\Sales\Model\Order\Config;
use \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\Collection;
use \Unirgy\Dropship\Helper\Data as DropshipHelperData;
use \Unirgy\Dropship\Model\Source;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    /**
     * @var Collection
     */
    protected $_itemCollection;

    /**
     * @var Config
     */
    protected $_orderConfig;

    /**
     * @var Source
     */
    protected $_modelSource;

    protected $_useBaseCostColumn = false;
    public function __construct(
        DropshipHelperData $helperData,
        Collection $itemCollection,
        Config $orderConfig,
        Source $modelSource,
        Context $context,
        HelperData $backendHelper, 
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        $this->_itemCollection = $itemCollection;
        $this->_orderConfig = $orderConfig;
        $this->_modelSource = $modelSource;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('reportItemGrid');
        $this->setDefaultSort('order_created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $res  = $this->_hlp->rHlp();
        $conn = $res->getConnection();
        $this->_useBaseCostColumn = $conn->tableColumnExists($res->getTableName('sales_order_item'), 'base_cost');
    }

    public function t($table)
    {
        return $this->_hlp->rHlp()->getTableName($table);
    }

    protected function _getFlatExpressionColumn($key, $bypass=true)
    {
        $result = $bypass ? $key : null;
        switch ($key) {
            case 'tax_amount':
                $result = new \Zend_Db_Expr("main_table.qty*((oi.base_tax_amount)/(if(oi.qty_ordered!=0&&oi.qty_ordered is not null, oi.qty_ordered, 1)))");
                break;
            case 'po_row_total':
                $result = new \Zend_Db_Expr("main_table.qty*((oi.base_row_total+oi.base_tax_amount)/(if(oi.qty_ordered!=0&&oi.qty_ordered is not null, oi.qty_ordered, 1)))");
                break;
        }
        return $result;
    }

    protected function _prepareCollection()
    {
        $res = $this->_hlp->rHlp();

        $collection = $this->_itemCollection;
        $collection->getSelect()
            ->join(array('oi'=>$res->getTableName('sales_order_item')), 'oi.item_id=main_table.order_item_id', array('discount_amount'=>'oi.base_discount_amount', 'cost'=>($this->_useBaseCostColumn ? 'oi.base_cost' : 'oi.cost'), 'tax_amount'=>$this->_getFlatExpressionColumn('tax_amount'),'base_price','po_row_total'=>$this->_getFlatExpressionColumn('po_row_total')))
            ->join(array('t'=>$res->getTableName('sales_shipment')), 't.entity_id=main_table.parent_id', array('udropship_vendor', 'udropship_available_at', 'udropship_method', 'udropship_method_description', 'udropship_status', 'base_shipping_amount', 'po_increment_id'=>'increment_id', 'created_at'))
            ->join(array('o'=>$res->getTableName('sales_order')), 'o.entity_id=oi.order_id', array('order_status'=>'o.status', 'order_increment_id'=>'o.increment_id', 'order_created_at'=>'o.created_at'))
			;

        $collection->getSelect()->where('oi.parent_item_id is null');
        
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $flat = true;
        
        $hlp = $this->_hlp;
        
        $this->addColumn('order_increment_id', array(
            'header'    => __('Order #'),
            'index'     => 'order_increment_id',
        	'filter_index' => !$flat ? null : 'o.increment_id',
            'type'      => 'number',
        ));

        $this->addColumn('order_created_at', array(
            'header'    => __('Order Date'),
            'index'     => 'order_created_at',
        	'filter_index' => !$flat ? null : 'o.created_at',
            'type'      => 'datetime',
        ));
        
        $this->addColumn('order_status', array(
            'header'    => __('Order Status'),
            'index'     => 'order_status',
            'filter_index' => !$flat ? null : 'o.status',
            'type' => 'options',
            'options' => $this->_orderConfig->getStatuses(),
        ));
        
        $this->addColumn('po_increment_id', array(
            'header'    => __('PO #'),
            'index'     => 'po_increment_id',
            'filter_index' => !$flat ? null : 't.increment_id',
            'type'      => 'text',
        ));

        $this->addColumn('created_at', array(
            'header'    => __('PO Date'),
            'index'     => 'created_at',
            'filter_index' => !$flat ? null : 't.created_at',
            'type'      => 'datetime',
        ));
        
        $this->addColumn('udropship_status', array(
            'header' => __('PO Status'),
            'index' => 'udropship_status',
            'filter_index' => !$flat ? null : 't.udropship_status',
            'type' => 'options',
            'options' => $this->_modelSource->setPath('shipment_statuses')->toOptionHash(),
        ));

        $this->addColumn('udropship_vendor', array(
            'header' => __('Vendor'),
            'index' => 'udropship_vendor',
        	'filter_index' => !$flat ? null : 't.udropship_vendor',
            'type' => 'options',
            'options' => $this->_modelSource->setPath('vendors')->toOptionHash(),
            'filter' => '\Unirgy\Dropship\Block\Vendor\GridColumnFilter'
        ));
        
        $this->addColumn('sku', array(
            'header' => __('PO Item SKU'),
            'index' => 'sku',
        	'filter_index' => !$flat ? null : 'main_table.sku',
        ));
        
        $this->addColumn('name', array(
            'header' => __('PO Item Name'),
            'index' => 'name',
        	'filter_index' => !$flat ? null : 'main_table.name',
        ));
        
        $this->addColumn('base_price', array(
            'header' => __('PO Item Price'),
            'index' => 'base_price',
        	'filter_index' => !$flat ? null : 'main_table.base_price',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
        ));
        
        $this->addColumn('discount_amount', array(
            'header' => __('PO Item Discount'),
            'index' => 'discount_amount',
        	'filter_index' => !$flat ? null : 'oi.base_discount_amount',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
        ));
        
        $this->addColumn('cost', array(
            'header' => __('PO Item Cost'),
            'index' => 'cost',
        	'filter_index' => !$flat ? null : 'oi.base_cost',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
        ));
        
        $this->addColumn('qty', array(
            'header'    => __('PO Item Qty'),
            'index'     => 'qty',
            'type'      => 'number',
        ));

        $this->addColumn('tax_amount', array(
            'header' => __('PO Item Tax'),
            'index' => 'tax_amount',
        	'filter_index' => !$flat ? null : $this->_getFlatExpressionColumn('tax_amount'),
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
        ));

        $this->addColumn('po_row_total', array(
            'header' => __('PO Item Row Total'),
            'index' => 'po_row_total',
            'filter_index' => !$flat ? null : $this->_getFlatExpressionColumn('po_row_total'),
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
        ));
        
        $this->addExportType('*/*/itemExportCsv', __('CSV'));
        $this->addExportType('*/*/itemExportXml', __('XML'));
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/itemGrid', array('_current'=>true));
    }
}
