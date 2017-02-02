<?php

namespace Unirgy\DropshipVendorRatings\Block\Adminhtml\Review;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended as WidgetGrid;
use Magento\Backend\Helper\Data as HelperData;
use Magento\Framework\Registry;
use Magento\Review\Model\ReviewFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorRatings\Model\ResourceModel\Review\Shipment\Collection;

class Grid extends WidgetGrid
{
    /**
     * @var ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    protected $_hlp;

    public function __construct(
        Context $context,
        HelperData $backendHelper, 
        ReviewFactory $modelReviewFactory, 
        Registry $frameworkRegistry,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        array $data = [])
    {
        $this->_reviewFactory = $modelReviewFactory;
        $this->_coreRegistry = $frameworkRegistry;
        $this->_hlp = $udropshipHelper;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('reviwGrid');
        $this->setDefaultSort('created_at');
    }

    protected function _prepareCollection()
    {
        $model = $this->_reviewFactory->create();
        $collection = $this->_hlp->createObj('\Unirgy\DropshipVendorRatings\Model\ResourceModel\Review\Shipment\Collection');
        $collection->joinReviews()->joinShipmentItemData();

        if ($this->getVendorId() || $this->getRequest()->getParam('vendorId', false)) {
            $this->setVendorId(($this->getVendorId() ? $this->getVendorId() : $this->getRequest()->getParam('vendorId')));
            $collection->addEntityFilter($this->getVendorId());
        }

        if ($this->getCustomerId() || $this->getRequest()->getParam('customerId', false)) {
            $this->setCustomerId(($this->getCustomerId() ? $this->getCustomerId() : $this->getRequest()->getParam('customerId')));
            $collection->addCustomerFilter($this->getCustomerId());
        }

        if ($this->_coreRegistry->registry('usePendingFilter') === true) {
            $collection->addStatusFilter($model->getPendingStatus());
        }

        $collection->addStoreData();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $statuses = $this->_reviewFactory->create()
            ->getStatusCollection()
            ->load()
            ->toOptionArray();

        foreach( $statuses as $key => $status ) {
            $tmpArr[$status['value']] = $status['label'];
        }

        $prefix = $this->uIsMassactionAvailable() ? '' : 'udratings_grid_';

        $statuses = $tmpArr;

        $this->addColumn($prefix.'review_id', [
            'header'        => __('ID'),
            'align'         => 'right',
            'width'         => '50px',
            'filter_index'  => 'rt.review_id',
            'index'         => 'review_id',
        ]);

        $this->addColumn($prefix.'created_at', [
            'header'        => __('Created On'),
            'align'         => 'left',
            'type'          => 'datetime',
            'width'         => '100px',
            'filter_index'  => 'rt.created_at',
            'index'         => 'created_at',
        ]);

        if( !$this->_coreRegistry->registry('usePendingFilter') ) {
            $this->addColumn($prefix.'status', [
                'header'        => __('Status'),
                'align'         => 'left',
                'type'          => 'options',
                'options'       => $statuses,
                'width'         => '100px',
                'filter_index'  => 'rt.status_id',
                'index'         => 'status_id',
            ]);
        }

        $this->addColumn($prefix.'title', [
            'header'        => __('Title'),
            'align'         => 'left',
            'width'         => '100px',
            'filter_index'  => 'rdt.title',
            'index'         => 'title',
            'type'          => 'text',
            'truncate'      => 50,
            'escape'        => true,
        ]);

        if (!$this->getCustomerId()) {
        $this->addColumn($prefix.'nickname', [
            'header'        => __('Nickname'),
            'align'         => 'left',
            'width'         => '100px',
            'filter_index'  => 'rdt.nickname',
            'index'         => 'nickname',
            'format'        => sprintf('<a href="%sid/$customer_id/">$nickname</a>', $this->getUrl('customer/index/edit'))
        ]);
        }

        $this->addColumn($prefix.'detail', [
            'header'        => __('Review'),
            'align'         => 'left',
            'index'         => 'detail',
            'filter_index'  => 'rdt.detail',
            'type'          => 'text',
            'truncate'      => 50,
            'nl2br'         => true,
            'escape'        => true,
        ]);

        /**
         * Check is single store mode
         */
        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn($prefix.'visible_in', [
                'header'    => __('Visible In'),
                'index'     => 'stores',
                'type'      => 'store',
                'store_view' => true,
            ]);
        }

        if (!$this->getVendorId()) {
        $this->addColumn($prefix.'udropship_vendor', [
            'header'        => __('Vendor'),
            'align'         => 'left',
            'width'         => '100px',
            'index'         => 'udropship_vendor',
            'format'        => sprintf('<a onclick="this.target=\'blank\'" href="%sid/$udropship_vendor/">$vendor_name</a>', $this->getUrl('udropship/vendor/edit'))
        ]);
        }

        $this->addColumn($prefix.'increment_id', [
            'header'        => __('Shipment'),
            'align'         => 'left',
            'width'         => '100px',
            'index'         => 'increment_id',
            'format'        => sprintf('<a onclick="this.target=\'blank\'" href="%sshipment_id/$entity_id/">$increment_id</a>', $this->getUrl('sales/shipment/view'))
        ]);

        $this->addColumn($prefix.'product_name_list', [
            'header'    => __('Product Name'),
            'align'     =>'left',
            'type'      => 'text',
            'index'     => 'product_name_list',
            'nl2br'     => true,
            'escape'    => true
        ]);

        $this->addColumn($prefix.'product_sku_list', [
            'header'    => __('Product SKU'),
            'align'     => 'right',
            'type'      => 'text',
            'width'     => '50px',
            'index'     => 'product_sku_list',
            'nl2br'     => true,
            'escape'    => true
        ]);

        $this->addColumn($prefix.'action',
            [
                'header'    => __('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getReviewId',
                'actions'   => [
                    [
                        'caption' => __('Edit'),
                        'url'     => [
                            'base'=>'udratings/review/edit',
                            'params'=> [
                                'vendorId' => $this->getVendorId(),
                                'customerId' => $this->getCustomerId(),
                                'ret'       => ( $this->_coreRegistry->registry('usePendingFilter') ) ? 'pending' : null
                            ]
                         ],
                         'field'   => 'id'
                    ]
                ],
                'filter'    => false,
                'sortable'  => false
        ]);

        return parent::_prepareColumns();
    }

    protected $_uIsMassactionAvailable = true;
    public function uIsMassactionAvailable($flag=null)
    {
        $result = $this->_uIsMassactionAvailable;
        if (null !== $flag) {
            $this->_uIsMassactionAvailable = $flag;
        }
        return $result;
    }
    public function setUisMassactionAvailable($flag)
    {
        $this->uIsMassactionAvailable($flag);
        return $this;
    }
    protected function _prepareMassaction()
    {
        if ($this->uIsMassactionAvailable()) {
            $this->setMassactionIdField('review_id');
            $this->setMassactionIdFieldOnlyIndexValue(true);
            $this->getMassactionBlock()->setFormFieldName('udratings');

            $this->getMassactionBlock()->addItem('delete', [
                'label'=> __('Delete'),
                'url'  => $this->getUrl('udratings/review/massDelete', ['ret' => $this->_coreRegistry->registry('usePendingFilter') ? 'pending' : 'index']),
                'confirm' => __('Are you sure?')
            ]);

            $statuses = $this->_reviewFactory->create()
                ->getStatusCollection()
                ->load()
                ->toOptionArray();
            array_unshift($statuses, ['label'=>'', 'value'=>'']);
            $this->getMassactionBlock()->addItem('update_status', [
                'label'         => __('Update Status'),
                'url'           => $this->getUrl('udratings/review/massUpdateStatus', ['ret' => $this->_coreRegistry->registry('usePendingFilter') ? 'pending' : 'index']),
                'additional'    => [
                    'status'    => [
                        'name'      => 'status',
                        'type'      => 'select',
                        'class'     => 'required-entry',
                        'label'     => __('Status'),
                        'values'    => $statuses
                    ]
                ]
            ]);
        }
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('udratings/review/edit', [
            'id' => $row->getReviewId(),
            'vendorId' => $this->getVendorId(),
            'customerId' => $this->getCustomerId(),
            'ret'       => ( $this->_coreRegistry->registry('usePendingFilter') ) ? 'pending' : null,
        ]);
    }

    public function getGridUrl()
    {
        if( $this->getVendorId() || $this->getCustomerId() ) {
            if ($this->uIsMassactionAvailable()) {
                return $this->getUrl('udratings/review/' . ($this->_coreRegistry->registry('usePendingFilter') ? 'pending' : ''), [
                    'vendorId' => $this->getVendorId(),
                    'customerId' => $this->getCustomerId(),
                ]);
            } elseif ($this->getVendorId()) {
                return $this->getUrl('udratings/review/VendorReviews', [
                    'vendorId' => $this->getVendorId(),
                ]);
            } else {
                return $this->getUrl('udratings/review/CustomerReviews', [
                    'customerId' => $this->getCustomerId(),
                ]);
            }
        } else {
            return $this->getCurrentUrl();
        }
    }
}
