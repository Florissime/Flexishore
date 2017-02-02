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

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Edit\Tab;

use \Magento\Backend\Block\Template\Context;
use \Magento\Backend\Block\Widget\Grid;
use \Magento\Backend\Helper\Data as HelperData;
use \Magento\CatalogInventory\Model\Stock;
use \Magento\Catalog\Model\Product;
use \Magento\Directory\Model\Currency;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Helper\Data as DropshipHelperData;
use \Unirgy\Dropship\Model\Vendor;

class Products extends \Magento\Backend\Block\Widget\Grid\Extended implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    /**
     * @var Resource
     */
    protected $_rHlp;

    public function __construct(
        Registry $registry,
        DropshipHelperData $helperData,
        \Unirgy\Dropship\Model\ResourceModel\Helper $resourceHelper,
        Context $context,
        HelperData $backendHelper, 
        array $data = []
    )
    {
        $this->_registry = $registry;
        $this->_hlp = $helperData;
        $this->_rHlp = $resourceHelper;

        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->setId('udropship_vendor_products');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
    }

    public function getVendor()
    {
        $vendor = $this->_registry->registry('vendor_data');
        if (!$vendor) {
            $vendor = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor')->load($this->getVendorId());
            $this->_registry->register('vendor_data', $vendor);
        }
        return $vendor;
    }

    protected function _addColumnFilterToCollection($column)
    {
        $id = $column->getId();
        $value = $column->getFilter()->getValue();
        $select = $this->getCollection()->getSelect();
        if ($this->_hlp->isUdmultiAvailable()) {
            switch ($id) {
            case 'vendor_sku':
                if (!is_null($value) && $value!=='') {
                    $select->where('vendor_sku like ?', $column->getFilter()->getValue().'%');
                }
                return $this;
    
            case 'vendor_cost':
                if (!is_null($value['from']) && $value['from']!=='') {
                    $select->where($id.'>=?', $value['from']);
                }
                if (!is_null($value['to']) && $value['to']!=='') {
                    $select->where($id.'<=?', $value['to']);
                }
                return $this;

            case 'backorders':
                $select->where($id.'=?', $column->getFilter()->getValue());
                return $this;

            case 'shipping_price':
                if (!is_null($value['from']) && $value['from']!=='') {
                    $select->where($id.'>=?', $value['from']);
                }
                if (!is_null($value['to']) && $value['to']!=='') {
                    $select->where($id.'<=?', $value['to']);
                }
                return $this;
            }
        }
        if ($this->_hlp->isUdmultiPriceAvailable()) {
            switch ($id) {
            case 'state':
                if (!is_null($value) && $value!=='') {
                    $select->where('state=?', $column->getFilter()->getValue());
                }
                return $this;

            case 'vendor_price':
                if (!is_null($value['from']) && $value['from']!=='') {
                    $select->where($id.'>=?', $value['from']);
                }
                if (!is_null($value['to']) && $value['to']!=='') {
                    $select->where($id.'<=?', $value['to']);
                }
                return $this;
            }
        }
        switch ($id) {
        case 'stock_qty':
            if (!is_null($value['from']) && $value['from']!=='') {
                $select->where($this->_getStockField('qty').'>=?', $value['from']);
            }
            if (!is_null($value['to']) && $value['to']!=='') {
                $select->where($this->_getStockField('qty').'<=?', $value['to']);
            }
            return $this;
        }
        // Set custom filter for in category flag
        if ($column->getId() == 'in_vendor') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', array('in'=>$productIds));
            }
            elseif(!empty($productIds)) {
                $this->getCollection()->addFieldToFilter('entity_id', array('nin'=>$productIds));
            }
        }
        else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }
    
    protected function _getStockField($type)
    {
        $v = $this->getVendor();
        if (!$v || !$v->getId()) {
            $isLocalVendor = 0;
        } else {
            $isLocalVendor = intval($v->getId()==$this->_scopeConfig->getValue('udropship/vendor/local_vendor'));
        }
        if ($this->_hlp->isUdmultiAvailable()) {
            switch ($type) {
                case 'qty':
                    return new \Zend_Db_Expr('IF(uvp.vendor_product_id is null, cisi.qty, uvp.stock_qty)');
                case 'status':
                    return new \Zend_Db_Expr("IF(uvp.vendor_product_id is null or $isLocalVendor, cisi.is_in_stock, null)");
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

    protected function _prepareCollection()
    {
        if ($this->getVendor()->getId()) {
            $this->setDefaultFilter(array('in_vendor'=>1));
        }
        $collection = $this->_hlp->createObj('\Magento\Catalog\Model\Product')->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('price')
            ->addStoreFilter($this->getRequest()->getParam('store'))
//            ->addAttributeToFilter('udropship_vendor', $this->getVendor()->getId());
//            ->addAttributeToFilter('type_id', array('in'=>array('simple')))
        ;
        
        $res = $this->_rHlp;
        $stockTable = $res->getTableName('cataloginventory_stock_item');
        $conn = $collection->getConnection();
        
        $collection->getSelect()->join(
            array('cisi' => $stockTable), 
            $conn->quoteInto('cisi.product_id=e.entity_id AND cisi.stock_id=?', Stock::DEFAULT_STOCK_ID), 
            array('_stock_status'=>$this->_getStockField('status'))
        );
        
        if ($this->_hlp->isUdmultiAvailable()) {
            $collection->getSelect()->joinLeft(
                array('uvp' => $res->getTableName('udropship_vendor_product')),
                $conn->quoteInto('uvp.product_id=e.entity_id AND uvp.vendor_id=?', $this->getVendor()->getId()), 
                array('*','_stock_qty'=>$this->_getStockField('qty'), 'vendor_sku'=>'uvp.vendor_sku', 'vendor_cost'=>'uvp.vendor_cost', 'backorders'=>'uvp.backorders')
            );
            $collection->getSelect()->columns(array('_stock_qty'=>$this->_getStockField('qty')));
            //$collection->getSelect()->columns(array('_stock_qty'=>'IFNULL(uvp.stock_qty,cisi.qty'));
        } else {
            $collection->getSelect()->columns(array('stock_qty'=>$this->_getStockField('qty')));
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('in_vendor', array(
            'header_css_class' => 'a-center',
            'type'      => 'checkbox',
            'name'      => 'in_vendor',
            'values'    => $this->_getSelectedProducts(),
            'align'     => 'center',
            'index'     => 'entity_id'
        ));
        $this->addColumn('entity_id', array(
            'header'    => __('ID'),
            'sortable'  => true,
            'width'     => '60',
            'index'     => 'entity_id'
        ));
        $this->addColumn('name', array(
            'header'    => __('Name'),
            'index'     => 'name'
        ));
        $this->addColumn('sku', array(
            'header'    => __('SKU'),
            'width'     => '80',
            'index'     => 'sku'
        ));
        if ($this->_hlp->isUdmultiAvailable()) {
            $this->addColumn('_vendor_sku', array(
                'header'    => __('Vendor SKU'),
                'index'     => 'vendor_sku',
                'editable'  => true, 'edit_only'=>true,
                'sortable'  => false,
                'filter'    => false,
                'width'     => '100',
            ));
        }
        $this->addColumn('price', array(
            'header'    => __('Price'),
            'type'  => 'currency',
            'currency_code' => (string) $this->_scopeConfig->getValue(Currency::XML_PATH_CURRENCY_BASE),
            'index'     => 'price'
        ));
        if ($this->_hlp->isUdmultiAvailable()) {
            $udmultiSrc = $this->_hlp->getObj('\Unirgy\DropshipMulti\Model\Source');
            $this->addColumn('_vendor_cost', array(
                'header'    => __('Vendor Cost'),
                'type'      => 'number',
                'index'     => 'vendor_cost',
                'editable'  => true, 'edit_only'=>true,
                'sortable'  => false,
                'filter'    => false,
            ));
            if ($this->_hlp->udmultiHlp()->isVendorProductShipping()) {
                $this->addColumn('_shipping_price', array(
                    'header'    => __('Shipping Price'),
                    'type'      => 'number',
                    'index'     => 'shipping_price',
                    'editable'  => true, 'edit_only'  => true,
                    'sortable'  => false,
                    'filter'    => false,
                ));
            }
            $this->addColumn('_status', array(
                'header'    => __('Status'),
                'type'      => 'select',
                'index'     => 'status',
                'editable'  => true, 'edit_only'  => true,
                'sortable'  => false,
                'filter'    => false,
                'options'   => $udmultiSrc->setPath('vendor_product_status')->toOptionHash()
            ));
        }
        if ($this->_hlp->isUdmultiPriceAvailable()) {
            $udmultiPriceSrc = $this->_hlp->getObj('\Unirgy\DropshipMultiPrice\Model\Source');
            $this->addColumn('_vendor_price', array(
                'header'    => __('Vendor Price'),
                'type'      => 'number',
                'index'     => 'vendor_price',
                'editable'  => true, 'edit_only'  => true,
                'sortable'  => false,
                'filter'    => false,
            ));
            $this->addColumn('_vendor_title', array(
                'header'    => __('Vendor Title'),
                'index'     => 'vendor_title',
                'editable'  => true, 'edit_only'  => true,
                'sortable'  => false,
                'filter'    => false,
                'width'     => '200',
            ));
            $this->addColumn('_state', array(
                'header'    => __('State/Condition'),
                'type'      => 'select',
                'index'     => 'state',
                'editable'  => true, 'edit_only'  => true,
                'sortable'  => false,
                'filter'    => false,
                'options'   => $udmultiPriceSrc->setPath('vendor_product_state')->toOptionHash()
            ));

        }
        if ($this->_hlp->isUdmultiAvailable()) {
            $udmultiSrc = $this->_hlp->getObj('\Unirgy\DropshipMulti\Model\Source');
            $this->addColumn('_backorders', array(
                'header'    => __('Backorders'),
                'type'      => 'select',
                'index'     => 'backorders',
                'editable'  => true, 'edit_only'=>true,
                'sortable'  => false,
                'filter'    => false,
                'options'   => $udmultiSrc->setPath('backorders')->toOptionHash()
            ));
        }
        $this->addColumn('_stock_qty', array(
            'header'    => __('Vendor Stock Qty'),
            'type'      => 'number',
            'index'     => 'stock_qty',
            'renderer'  => '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\GridRenderer\StockQty',
            'editable'  => true, 'edit_only'  => true,
            'sortable'  => false,
            'filter'    => false
        ));
        if ($this->_hlp->isUdmultiPriceAvailable()) {
            $this->addColumn('_special_price', array(
                'header'    => __('Special Price'),
                'index'     => 'special_price',
                'renderer'  => '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\GridRenderer\SpecialPrice',
                'editable'  => true, 'edit_only'  => true,
                'sortable'  => false,
                'filter'    => false,
            ));
        }
        /*
        $this->addColumn('priority', array(
            'header'    => __('Priority'),
            'width'     => '70',
            'type'      => 'number',
            'index'     => 'priority',
            'editable'  => true
            'renderer'  => 'adminhtml/widget_grid_column_renderer_input'
        ));
        */
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/productGrid', array('_current'=>true));
    }

    protected function _getSelectedProducts()
    {
        $json = $this->getRequest()->getPost('vendor_products');
        if (!is_null($json)) {
            $products = array_keys((array)\Zend_Json::decode($json));
        } else {
            $products = $this->getVendor()->getAssociatedProductIds();
        }
        return $products;
    }

    public function getTabLabel()
    {
        return __('Associated Products');
    }
    public function getTabTitle()
    {
        return __('Associated Products');
    }
    public function canShowTab()
    {
        return true;
    }
    public function isHidden()
    {
        return false;
    }

    public function getAdditionalJavaScript()
    {
        $collection = $this->_hlp->getShippingMethods();
        $carriers = array();
        foreach ($collection as $s) {
            if (!$s->getSystemMethods()) {
                $carriers[$s->getId()] = array();
                continue;
            }
            foreach ($s->getSystemMethods() as $k => $v) {
                $carriers[$s->getId()][$k] = $v;
            }
        }
        ob_start();
?>
if (!$('vendor_products').value) {
    $('vendor_products').value = '{}';
}
var vendorProducts = $('vendor_products').value.evalJSON();

function changeVendorProductProperty() {
    if (!vendorProducts[this.productId]) {
        vendorProducts[this.productId] = {};
    }
    if (!this.name) {
        return;
    }
    var fname = this.name.replace(/^_/, '');
    vendorProducts[this.productId][fname] = this.value;
    highlightProductRow(this);
    $('vendor_products').value = Object.toJSON(vendorProducts);
}

function highlightProductRow(input, changed) {
    return; // disabled until done properly
    $(input).up('tr').select('td').each(function (el) {
        el.style.backgroundColor = changed || typeof changed == 'undefined' ? '#ffb' : '';
    });
}

udropship_vendor_productsJsObject.initCallback = function (self) {
    self.initGridRows && self.initGridRows();
}

udropship_vendor_productsJsObject.initRowCallback = function (self, row) {
    var inputs = $(row).select('input', 'select'), id, selected, fname;
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].type == 'checkbox' && inputs[i].name == '') {
            id = inputs[i].value;
            if (vendorProducts[id] && (typeof vendorProducts[id]['on'] !== 'undefined')) {
                selected = vendorProducts[id]['on'];
                inputs[i].checked = selected;
                highlightProductRow(inputs[i]);
            } else {
                selected = inputs[i].checked;
            }
        } else {
            inputs[i].disabled = !selected;
            inputs[i].productId = id;
            fname = inputs[i].name.replace(/^_/, '');
            if (vendorProducts[id] && vendorProducts[id][fname]) {
                inputs[i].value = vendorProducts[id][fname];
            }
            $(inputs[i]).observe('change', changeVendorProductProperty);
        }
    }
}

udropship_vendor_productsJsObject.checkboxCheckCallback = function (grid, element, checked) {
    $(element).up('tr').select('input', 'select').each(function (el) {
        if (el.type == 'checkbox' && el.name == '') {
            if (!vendorProducts[el.value]) {
                vendorProducts[el.value] = {};
            }
            vendorProducts[el.value]['on'] = checked;
            highlightProductRow(element);
        } else {
            el.disabled = !checked;
        }
    });
    $('vendor_products').value = Object.toJSON(vendorProducts);
}

udropship_vendor_productsJsObject.rowClickCallback = function (grid, event) {
    var trElement = Event.findElement(event, 'tr');
    var isInput = Event.element(event).tagName.match(/(input|select|option)/i);
    if (trElement) {
        var checkbox = Element.getElementsBySelector(trElement, 'input');
        if (checkbox[0]) {
            var checked = isInput ? checkbox[0].checked : !checkbox[0].checked;
            udropship_vendor_productsJsObject.setCheckboxChecked(checkbox[0], checked);
        }
    }
}
udropship_vendor_productsJsObject.initGrid();
<?php
        return ob_get_clean();
    }
}
