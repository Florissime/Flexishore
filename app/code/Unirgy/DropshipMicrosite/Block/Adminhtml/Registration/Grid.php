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
 * @package    Unirgy_DropshipMicrosite
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipMicrosite\Block\Adminhtml\Registration;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid as WidgetGrid;
use Magento\Backend\Helper\Data as HelperData;
use Unirgy\DropshipMicrosite\Helper\Data as DropshipMicrositeHelperData;
use Unirgy\DropshipMicrosite\Model\RegistrationFactory;
use Unirgy\Dropship\Model\Source;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var RegistrationFactory
     */
    protected $_registrationFactory;

    /**
     * @var DropshipMicrositeHelperData
     */
    protected $_msHlp;

    /**
     * @var Source
     */
    protected $_src;


    public function __construct(Context $context, 
        HelperData $backendHelper, 
        RegistrationFactory $modelRegistrationFactory, 
        DropshipMicrositeHelperData $helperData, 
        Source $modelSource, 
        array $data = [])
    {
        $this->_registrationFactory = $modelRegistrationFactory;
        $this->_msHlp = $helperData;
        $this->_src = $modelSource;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('registrationGrid');
        $this->setDefaultSort('reg_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('reg_filter');
    }

    protected function _prepareCollection()
    {
        $collection = $this->_registrationFactory->create()->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $hlp = $this->_msHlp;
        $this->addColumn('reg_id', [
            'header'    => __('Registration ID'),
            'align'     => 'right',
            'width'     => '50px',
            'index'     => 'reg_id',
            'type'      => 'number',
        ]);

        $this->addColumn('vendor_name', [
            'header'    => __('Vendor Name'),
            'index'     => 'vendor_name',
        ]);

        $this->addColumn('email', [
            'header'    => __('Email'),
            'index'     => 'email',
        ]);

        $this->addColumn('carrier_code', [
            'header'    => __('Used Carrier'),
            'index'     => 'carrier_code',
            'type'      => 'options',
            'options'   => $this->_src->setPath('carriers')->toOptionHash(),
        ]);

        $this->addExportType('*/*/exportCsv', __('CSV'));
        $this->addExportType('*/*/exportXml', __('XML'));
        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', ['reg_id' => $row->getId()]);
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('vendor');

        $this->getMassactionBlock()->addItem('delete', [
             'label'=> __('Delete'),
             'url'  => $this->getUrl('*/*/massDelete'),
             'confirm' => __('Are you sure?')
        ]);

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current'=>true]);
    }
}
