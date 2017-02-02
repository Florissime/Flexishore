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
use \Magento\Framework\Event\ManagerInterface;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Helper\Data as DropshipHelperData;
use \Unirgy\Dropship\Model\Source;
use \Unirgy\Dropship\Model\Vendor;

class Shipping extends \Magento\Backend\Block\Widget\Grid\Extended implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    public function __construct(
        Registry $registry,
        DropshipHelperData $helperData,
        Context $context,
        HelperData $backendHelper, 
        array $data = []
    )
    {
        $this->_registry = $registry;
        $this->_hlp = $helperData;

        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->setId('udropship_vendor_shipping');
        $this->setDefaultSort('days_in_transit');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
    }

    public function getVendor()
    {
        $vendor = $this->_registry->registry('vendor_data');
        if (!$vendor) {
            $vendor = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor')->load($this->getVendorId());
        }
        return $vendor;
    }
/*
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in category flag
        if ($column->getId() == 'in_vendor') {
            $productIds = $this->_getSelectedMethods();
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
*/
    protected function _prepareCollection()
    {
        $collection = $this->_hlp->createObj('\Unirgy\Dropship\Model\Shipping')->getCollection()
            ->joinVendor($this->getVendorId());
        $this->setCollection($collection);

        $this->_eventManager->dispatch('udropship_adminhtml_vendor_edit_prepare_shipping_grid', array('grid'=>$this, 'collection'=>$collection, 'vendor'=>$this->getVendor()));

        parent::_prepareCollection();
        if (!$this->getVendorId() && ($v = $this->getVendor()) && ($_ps = $v->getPostedShipping())) {
            foreach ($this->getCollection() as $item) {
                $sId = $item->getShippingId();
                if (isset($_ps[$sId]) && is_array($_ps[$sId])) {
                    $item->addData($_ps[$sId]);
                }
            }
        }
        return $this;
    }

    protected function _prepareColumns()
    {
        $hlp = $this->_hlp;
        $this->addColumn('in_vendor', array(
            'header_css_class' => 'a-center',
            'type'      => 'checkbox',
            'name'      => 'in_vendor',
            'values'    => $this->_getSelectedMethods(),
            'align'     => 'center',
            'index'     => 'shipping_id'
        ));
        $this->addColumn('shipping_code', array(
            'header'    => __('Code'),
            'index'     => 'shipping_code'
        ));
        $this->addColumn('shipping_title', array(
            'header'    => __('Title'),
            'index'     => 'shipping_title'
        ));

        $this->addColumn('days_in_transit', array(
            'header'    => __('Days In Transit'),
            'index'     => 'days_in_transit'
        ));

        if ($this->getVendor()->getAllowShippingExtraCharge()) {
            $this->addColumn('_allow_extra_charge', array(
                'header'    => __('Extra Charge'),
                'index'     => 'allow_extra_charge',
                'renderer'  => '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\GridRenderer\ShippingExtraCharge',
                'sortable'  => false,
                'filter'    => false,
                'field_id_tpl' => '_%s',
                'vendor' => $this->getVendor()
            ));
        }

        $carriers = $this->_hlp->src()->setPath('carriers')->toOptionHash(true);
        $carriers[''] = __('* Use Default');

        $this->addColumn('_est_carrier_code', array(
            'header'    => __('Estimate Carrier'),
            'index'     => 'est_carrier_code',
            'sortable'  => false,
            'filter'    => false,
            'type'      => 'select',
            'options'   => $carriers,
        ));

        $carriers['**estimate**'] = __('* Use Estimate');

        $this->addColumn('_carrier_code', array(
            'header'    => __('Carrier Override'),
            'index'     => 'carrier_code',
            'sortable'  => false,
            'filter'    => false,
            'type'      => 'select',
            'options'   => $carriers,
        ));

        if ($this->getVendor()->getAllowShippingExtraCharge()) {
            $this->addColumn('_priority', array(
                'header'    => __('Priority'),
                'index'     => 'priority',
                'sortable'  => false,
                'filter'    => false,
                'editable'  => true, 'edit_only'=>true,
            ));
        }

        $this->addColumn('_default', array(
            'header'    => __('Default'),
            'index'     => 'shipping_id',
            'sortable'  => false,
            'filter'    => false,
            'editable'  => true, 'edit_only'=>true,
            'type'      => 'radio',
            'html_name' => 'default_shipping_id',
            'value'     => $this->getVendor()->getDefaultShippingId()
        ));

        $this->_eventManager->dispatch('udropship_adminhtml_vendor_shipping_grid_prepare_columns', array('grid'=>$this));

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/shippingGrid', array('_current'=>true));
    }

    protected function _getSelectedMethods()
    {
        $json = $this->getRequest()->getPost('vendor_shipping');
        if (!is_null($json)) {
            $methods = array_keys((array)\Zend_Json::decode($json));
        } else {
            $methods = array_keys($this->getVendor()->getAssociatedShippingMethods());
        }
        return $methods;
    }

    public function getTabLabel()
    {
        return __('Shipping methods');
    }
    public function getTabTitle()
    {
        return __('Shipping methods');
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
            foreach ($s->getSystemMethods() as $k=>$v) {
                $carriers[$s->getId()][$k] = $v;
            }
        }
        ob_start();
?>
var allowedCarriers = <?php echo \Zend_Json::encode($carriers) ?>;
if (!$('vendor_shipping').value) {
    $('vendor_shipping').value = '{}';
}
var vendorShipping = $('vendor_shipping').value.evalJSON();

function changeVendorShippingProperty() {
    if (!vendorShipping[this.shippingId]) {
        vendorShipping[this.shippingId] = {};
    }
    if (!this.name) {
        return;
    }
    var fname = this.name.replace(/^_/, '');
    vendorShipping[this.shippingId][fname] = this.value;
    highlightProductRow(this);
    $('vendor_shipping').value = Object.toJSON(vendorShipping);
}

function highlightProductRow(input, changed) {
    return; // disabled until done properly
    $(input).up('tr').select('td').each(function (el) {
        el.style.backgroundColor = changed || typeof changed == 'undefined' ? '#ffb' : '';
    });
}

udropship_vendor_shippingJsObject.initCallback = function (self) {
    self.initGridRows && self.initGridRows();
}

udropship_vendor_shippingJsObject.initRowCallback = function (self, row) {
    var inputs = $(row).select('input', 'select'), id, selected, fname;
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].type == 'checkbox' && inputs[i].name == '') {
            id = inputs[i].value;
            if (vendorShipping[id] && (typeof vendorShipping[id]['on'] !== 'undefined')) {
                selected = vendorShipping[id]['on'];
                inputs[i].checked = selected;
                highlightProductRow(inputs[i]);
            } else {
                selected = inputs[i].checked;
            }
        } else {
            inputs[i].disabled = !selected;
            inputs[i].shippingId = id;
            fname = inputs[i].name.replace(/^_/, '');
            if (vendorShipping[id] && vendorShipping[id][fname]) {
                inputs[i].value = vendorShipping[id][fname];
            }
            if (inputs[i].tagName.match(/select/i) && inputs[i].name.match(/carrier_code/i)) {
                for (var j = 0; j < inputs[i].options.length; j++) {
                    if (inputs[i].options[j].value && inputs[i].options[j].value != '**estimate**' && !allowedCarriers[id][inputs[i].options[j].value]) {
                        Element.remove(inputs[i].options[j]);
                        j--;
                    }
                }
            }
            $(inputs[i]).observe('change', changeVendorShippingProperty);
        }
    }
}

udropship_vendor_shippingJsObject.checkboxCheckCallback = function (grid, element, checked) {
    $(element).up('tr').select('input', 'select').each(function (el) {
        if (el.type == 'checkbox' && el.name == '') {
            if (!vendorShipping[el.value]) {
                vendorShipping[el.value] = {};
            }
            vendorShipping[el.value]['on'] = checked;
            highlightProductRow(element);
        } else {
            el.disabled = !checked;
        }
    });
    $('vendor_shipping').value = Object.toJSON(vendorShipping);
}

udropship_vendor_shippingJsObject.rowClickCallback = function (grid, event) {
    var trElement = Event.findElement(event, 'tr');
    var isInput = Event.element(event).tagName.match(/(input|select|option)/i);
    if (trElement) {
        var checkbox = Element.getElementsBySelector(trElement, 'input');
        if (checkbox[0]) {
            var checked = isInput ? checkbox[0].checked : !checkbox[0].checked;
            udropship_vendor_shippingJsObject.setCheckboxChecked(checkbox[0], checked);
        }
    }
}
udropship_vendor_shippingJsObject.initGrid();
<?php
        return ob_get_clean();
    }

}
