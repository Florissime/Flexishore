<?php

namespace Unirgy\Dropship\Block\Adminhtml\Batch;

use \Magento\Backend\Block\Template\Context;
use \Magento\Backend\Block\Widget\Grid\Extended as WidgetGrid;
use \Magento\Backend\Helper\Data as HelperData;
use \Unirgy\Dropship\Model\Label\Batch;
use \Unirgy\Dropship\Model\Source;

class Grid extends WidgetGrid
{
    /**
     * @var Batch
     */
    protected $_labelBatch;

    /**
     * @var Source
     */
    protected $_modelSource;


    public function __construct(
        Batch $labelBatch,
        Source $modelSource,
        Context $context,
        HelperData $backendHelper, 
        array $data = []
    )
    {
        $this->_labelBatch = $labelBatch;
        $this->_modelSource = $modelSource;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('batchGrid');
        $this->setDefaultSort('batch_id');
        $this->setDefaultDir('desc');
    }

    protected function _prepareCollection()
    {
        $collection = $this->_labelBatch->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $baseUrl = $this->getUrl();

        $this->addColumn('batch_id', array(
            'header'    => __('ID'),
            'index'     => 'batch_id',
            'width'     => 10,
            'type'      => 'number',

        ));

        $this->addColumn('created_at', array(
            'header'    => __('Created At'),
            'index'     => 'created_at',
            'type'      => 'datetime',
            'width'     => 150,
        ));

        $this->addColumn('title', array(
            'header'    => __('Title'),
            'index'     => 'title',
        ));

        $this->addColumn('vendor_id', array(
            'header' => __('Vendor'),
            'index' => 'vendor_id',
            'type' => 'options',
            'options' => $this->_modelSource->setPath('vendors')->toOptionHash(),
        ));

        $this->addColumn('label_type', array(
            'header' => __('Label Type'),
            'index' => 'label_type',
            'type' => 'options',
            'options' => $this->_modelSource->setPath('label_type')->toOptionHash(),
        ));

        $this->addColumn('shipment_cnt', array(
            'header'    => __('# of Shipments'),
            'index'     => 'shipment_cnt',
            'type'      => 'number',
        ));

        $this->addColumn('page_actions', array(
            'header'    => __('Action'),
            'width'     => 150,
            'sortable'  => false,
            'filter'    => false,
            'renderer'  => '\Unirgy\Dropship\Block\Adminhtml\Batch\Action',
        ));

        return parent::_prepareColumns();
    }
}