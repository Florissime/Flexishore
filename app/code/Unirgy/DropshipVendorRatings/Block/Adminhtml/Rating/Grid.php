<?php

namespace Unirgy\DropshipVendorRatings\Block\Adminhtml\Rating;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended as WidgetGrid;
use Magento\Backend\Helper\Data as HelperData;
use Magento\Framework\Registry;
use Magento\Review\Model\RatingFactory;
use Unirgy\Dropship\Model\Source;

class Grid extends WidgetGrid
{
    /**
     * @var RatingFactory
     */
    protected $_ratingFactory;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var Source
     */
    protected $_src;


    public function __construct(Context $context, 
        HelperData $backendHelper, 
        RatingFactory $modelRatingFactory, 
        Registry $frameworkRegistry, 
        Source $modelSource, 
        array $data = [])
    {
        $this->_ratingFactory = $modelRatingFactory;
        $this->_coreRegistry = $frameworkRegistry;
        $this->_src = $modelSource;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('ratingsGrid');
        $this->setDefaultSort('rating_code');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = $this->_ratingFactory->create()
            ->getResourceCollection()
            ->addEntityFilter($this->_coreRegistry->registry('entityId'));
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('rating_id', [
            'header'    => __('ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'rating_id',
        ]);

        $this->addColumn('rating_code', [
            'header'    => __('Rating Name'),
            'align'     =>'left',
            'index'     => 'rating_code',
        ]);

        $this->addColumn('is_aggregate', [
            'header'    => __('Is Aggregatable'),
            'align'     =>'center',
            'index'     => 'is_aggregate',
            'type' => 'options',
            'options' => $this->_src->setPath('yesno')->toOptionHash(),
        ]);

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', ['id' => $row->getId()]);
    }

}
