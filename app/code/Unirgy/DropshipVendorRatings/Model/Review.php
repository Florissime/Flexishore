<?php

namespace Unirgy\DropshipVendorRatings\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory;
use Magento\Review\Model\ResourceModel\Review\Status\CollectionFactory as StatusCollectionFactory;
use Magento\Review\Model\ResourceModel\Review\Summary\CollectionFactory as SummaryCollectionFactory;
use Magento\Review\Model\Review as ModelReview;
use Magento\Review\Model\Review\Summary;
use Magento\Review\Model\Review\SummaryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class Review extends ModelReview
{
    /**
     * @var HelperData
     */
    protected $_rateHlp;

    public function __construct(Context $context, 
        Registry $registry, 
        CollectionFactory $productFactory, 
        StatusCollectionFactory $statusFactory, 
        SummaryCollectionFactory $summaryFactory, 
        SummaryFactory $summaryModFactory, 
        Summary $reviewSummary, 
        StoreManagerInterface $storeManager, 
        UrlInterface $urlModel, 
        HelperData $helperData, 
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        array $data = [])
    {
        $this->_rateHlp = $helperData;

        parent::__construct($context, $registry, $productFactory, $statusFactory, $summaryFactory, $summaryModFactory, $reviewSummary, $storeManager, $urlModel, $resource, $resourceCollection, $data);
    }

    public function aggregate()
    {
        $this->_rateHlp->useEt($this->getEntityId());
        parent::aggregate();
        $this->_rateHlp->resetEt();
        return $this;
    }

    public function getEntitySummary($product, $storeId=0)
    {
        $this->_rateHlp->useEt($this->getEntityId());
        parent::getEntitySummary($product);
        $this->_rateHlp->resetEt();
    }
}