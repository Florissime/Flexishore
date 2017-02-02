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

namespace Unirgy\Dropship\Block\Adminhtml\Shipping;

use \Magento\Backend\Block\Template\Context;
use \Magento\Backend\Block\Widget\Grid as WidgetGrid;
use \Magento\Backend\Helper\Data as HelperData;
use \Magento\Framework\Event\ManagerInterface;
use \Magento\Framework\View\LayoutFactory;
use \Magento\Store\Model\Website;
use \Unirgy\Dropship\Helper\Data as DropshipHelperData;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    /**
     * @var Website
     */
    protected $_websiteOptions;

    public function __construct(
        DropshipHelperData $helperData,
        LayoutFactory $viewLayoutFactory,
        \Magento\Config\Model\Config\Source\Website\OptionHash $websiteOptions,
        Context $context,
        HelperData $backendHelper, 
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        $this->_websiteOptions = $websiteOptions;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('shippingGrid');
        $this->setDefaultSort('days_in_transit');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('shipping_filter');
    }

    protected function _prepareCollection()
    {
        $collection = $this->_hlp->getShippingMethods();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $hlp = $this->_hlp;

        $this->addColumn('shipping_code', array(
            'header'    => __('Method Code'),
            'index'     => 'shipping_code',
        ));

        $this->addColumn('shipping_title', array(
            'header'    => __('Method Title'),
            'index'     => 'shipping_title',
        ));

        $this->addColumn('days_in_transit', array(
            'header'    => __('Days In Transit'),
            'index'     => 'days_in_transit',
        ));

        $this->addColumn('website_ids', array(
            'header'        => __('Website'),
            'index'         => 'website_ids',
            'type'          => 'options',
            'options'       => $this->_websiteOptions->toOptionArray(),
            'sortable'      => false,
            'filter_condition_callback'
                            => array($this, '_filterWebsiteCondition'),
        ));

        $this->addColumn('system_methods_by_profile', array(
            'header'    => __('System Methods'),
            'index'     => 'system_methods_by_profile',
            'filter'    => false,
            'sortable'  => false,
        ));

        $this->_eventManager->dispatch('udropship_adminhtml_shipping_grid_prepare_columns', array('grid'=>$this));

        $column = $this->getColumn('system_methods_by_profile');
        $column->setRenderer($this->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\Shipping\Grid\Renderer')->setColumn($column));

        $column = $this->getColumn('website_ids');
        $column->setRenderer($this->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\Shipping\Grid\Renderer')->setColumn($column));

        $this->addExportType('*/*/exportCsv', __('CSV'));
        $this->addExportType('*/*/exportXml', __('XML'));
        return parent::_prepareColumns();
    }
    
    protected function _afterLoadCollection()
    {
        $this->_eventManager->dispatch('udropship_adminhtml_shipping_grid_after_load', array('grid'=>$this));
        return $this;
    }

    protected function _filterWebsiteCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $this->getCollection()->addWebsiteFilter($value);
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('shipping');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'=> __('Delete'),
             'url'  => $this->getUrl('*/*/massDelete'),
             'confirm' => __('Are you sure?')
        ));

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
