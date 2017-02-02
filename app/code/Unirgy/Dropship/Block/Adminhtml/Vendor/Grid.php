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

namespace Unirgy\Dropship\Block\Adminhtml\Vendor;

use \Magento\Backend\Block\Template\Context;
use \Magento\Backend\Helper\Data as HelperData;
use \Magento\Framework\Event\ManagerInterface;
use \Unirgy\Dropship\Helper\Data as DropshipHelperData;
use \Unirgy\Dropship\Model\Source;
use \Unirgy\Dropship\Model\Vendor;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    public function __construct(
        DropshipHelperData $helperData,
        Context $context,
        HelperData $backendHelper, 
        array $data = []
    )
    {
        $this->_hlp = $helperData;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('vendorGrid');
        $this->setDefaultSort('vendor_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('vendor_filter');
    }

    protected function _prepareCollection()
    {
        $collection = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $hlp = $this->_hlp;
        $this->addColumn('vendor_id', array(
            'header'    => __('Vendor ID'),
            'align'     => 'right',
            'width'     => '50px',
            'index'     => 'vendor_id',
            'type'      => 'number',
        ));

        $this->addColumn('vendor_name', array(
            'header'    => __('Vendor Name'),
            'index'     => 'vendor_name',
        ));

        $this->addColumn('email', array(
            'header'    => __('Email'),
            'index'     => 'email',
        ));

        if ($hlp->isModuleActive('Unirgy_DropshipStockPo')) {
            $this->addColumn('distributor_id', array(
                'header' => __('Distributor'),
                'index' => 'distributor_id',
                'type' => 'options',
                'options' => $this->_hlp->src()->setPath('vendors')->toOptionHash(),
            ));
        }

        $this->addColumn('carrier_code', array(
            'header'    => __('Used Carrier'),
            'index'     => 'carrier_code',
            'type'      => 'options',
            'options'   => $this->_hlp->src()->setPath('carriers')->toOptionHash(),
        ));

        if ($this->_hlp->isUdsprofileActive()) {
            $this->addColumn('shipping_profile', array(
                'header'    => __('Shipping Profile'),
                'index'     => 'shipping_profile',
                'type'      => 'options',
                'options'   => $this->_hlp->getObj('\Unirgy\DropshipShippingProfile\Model\Source')->setPath('profiles')->toOptionHash(),
            ));
        }

        $this->addColumn('status', array(
            'header'    => __('Status'),
            'index'     => 'status',
            'type'      => 'options',
            'options'   => $this->_hlp->src()->setPath('vendor_statuses')->toOptionHash(),
        ));

        $this->addColumn('action',
            array(
                'header'    => __('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => __('View'),
                        'url'     => array('base'=>'udropship/vendor/edit'),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'is_system' => true
        ));

        $this->_eventManager->dispatch('udropship_adminhtml_vendor_grid_prepare_columns', array('grid'=>$this));

        $this->addExportType('*/*/exportCsv', __('CSV'));
        $this->addExportType('*/*/exportXml', __('XML'));
        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('vendor_id');
        $this->getMassactionBlock()->setFormFieldName('vendor');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'=> __('Delete'),
             'url'  => $this->getUrl('*/*/massDelete'),
             'confirm' => __('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('status', array(
             'label'=> __('Change status'),
             'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
             'additional' => array(
                    'status' => array(
                         'name' => 'status',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => __('Status'),
                         'values' => $this->_hlp->src()->setPath('vendor_statuses')->toOptionArray(true),
                     )
             )
        ));

        $this->getMassactionBlock()->addItem('carrier_code', array(
            'label'=> __('Change Preferred Carrier'),
            'url'  => $this->getUrl('*/*/massCarrierCode', array('_current'=>true)),
            'additional' => array(
                'carrier_code' => array(
                    'name' => 'carrier_code',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => __('Preferred Carrier'),
                    'values' => $this->_hlp->src()->setPath('carriers')->toOptionHash(true),
                )
            )
        ));

        if ($this->_hlp->isUdsprofileActive()) {
            $this->getMassactionBlock()->addItem('shipping_profile', array(
                'label'=> __('Change Shipping Profile'),
                'url'  => $this->getUrl('*/*/massShippingProfile', array('_current'=>true)),
                'additional' => array(
                    'shipping_profile' => array(
                        'name' => 'shipping_profile',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => __('Shipping Profile'),
                        'values' => $this->_hlp->getObj('\Unirgy\DropshipShippingProfile\Model\Source')->setPath('profiles')->toOptionHash(true),
                    )
                )
            ));
        }

        $this->_eventManager->dispatch('udropship_adminhtml_vendor_grid_prepare_massaction', array('grid'=>$this));

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
