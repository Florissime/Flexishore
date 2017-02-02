<?php

namespace Unirgy\DropshipShippingClass\Block\Adminhtml\Customer;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended as WidgetGrid;
use Magento\Backend\Helper\Data as HelperData;
use Unirgy\DropshipShippingClass\Model\CustomerFactory;

class Grid extends WidgetGrid
{
    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    public function __construct(
        Context $context,
        HelperData $backendHelper,
        CustomerFactory $modelCustomerFactory,
        array $data = []
    ) {
        $this->_customerFactory = $modelCustomerFactory;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('udshipclassCustomerGrid');
        $this->setDefaultSort('class_name');
        $this->setDefaultDir('ASC');
    }

    protected function _prepareCollection()
    {
        $collection = $this->_customerFactory->create()
            ->getCollection()
            ->setFlag('load_region_labels', true);
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('class_name',
                         [
                             'header' => __('Class Name'),
                             'align' => 'left',
                             'index' => 'class_name'
                         ]
        );

        $this->addColumn('country_id', [
            'header' => __('Country'),
            'type' => 'text',
            'align' => 'left',
            'index' => 'country_id',
            'renderer' => 'Unirgy\DropshipShippingClass\Block\Adminhtml\GridRenderer\Countries',
            'filter' => false,
            'sortable' => false
        ]);

        $this->addColumn('region_name', [
            'header' => __('State/Region'),
            'header_export' => __('State'),
            'align' => 'left',
            'index' => 'region_name',
            'type' => 'text',
            'renderer' => 'Unirgy\DropshipShippingClass\Block\Adminhtml\GridRenderer\Regions',
            'filter' => false,
            'sortable' => false,
            'nl2br' => true,
            'default' => '*',
        ]);

        $this->addColumn('postcode', [
            'header' => __('Zip/Post Code'),
            'align' => 'left',
            'index' => 'postcode',
            'type' => 'text',
            'renderer' => 'Unirgy\DropshipShippingClass\Block\Adminhtml\GridRenderer\Postcodes',
            'filter' => false,
            'sortable' => false,
            'nl2br' => true,
            'default' => '*',
        ]);

        $this->addColumn('sort_order', [
            'header' => __('Sort Order'),
            'align' => 'left',
            'index' => 'sort_order',
        ]);

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', ['id' => $row->getId()]);
    }

}
