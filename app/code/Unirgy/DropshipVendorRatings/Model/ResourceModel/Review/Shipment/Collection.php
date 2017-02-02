<?php

namespace Unirgy\DropshipVendorRatings\Model\ResourceModel\Review\Shipment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Rating\Option\VoteFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection as ShipmentCollection;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class Collection extends ShipmentCollection
{
    /**
     * @var HelperData
     */
    protected $_rateHlp;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var RatingFactory
     */
    protected $_ratingFactory;

    /**
     * @var VoteFactory
     */
    protected $_optionVoteFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    protected $_hlp;

    protected $_reviewStoreTable;

    public function __construct(EntityFactoryInterface $entityFactory, 
        LoggerInterface $logger, 
        FetchStrategyInterface $fetchStrategy, 
        ManagerInterface $eventManager, 
        Snapshot $entitySnapshot, 
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        HelperData $helperData, 
        ScopeConfigInterface $configScopeConfigInterface, 
        RatingFactory $modelRatingFactory, 
        VoteFactory $optionVoteFactory, 
        StoreManagerInterface $modelStoreManagerInterface, 
        AdapterInterface $connection = null, 
        AbstractDb $resource = null)
    {
        $this->_hlp = $udropshipHelper;
        $this->_rateHlp = $helperData;
        $this->_scopeConfig = $configScopeConfigInterface;
        $this->_ratingFactory = $modelRatingFactory;
        $this->_optionVoteFactory = $optionVoteFactory;
        $this->_storeManager = $modelStoreManagerInterface;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $entitySnapshot, $connection, $resource);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->_reviewStoreTable = $this->_hlp->rHlp()->getTableName('review_store');
    }
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()
            ->join(['udv'=>$this->getTable('udropship_vendor')],
                   'main_table.udropship_vendor=udv.vendor_id and udv.allow_udratings>0',
                   ['vendor_name', 'vendor_email'=>'email']);
        return $this;
    }
    public function addCustomerFilter($customer)
    {
        $this->addFieldToFilter('main_table.customer_id', is_scalar($customer) || is_null($customer) ? $customer : $customer->getId());
        return $this;
    }
    public function addPendingFilter()
    {
        $reviewTable = $this->_hlp->rHlp()->getTableName('review');
        $this->getSelect()
            ->joinLeft(['rt' => $reviewTable],
                'rt.rel_entity_pk_value = main_table.entity_id and rt.entity_id='.$this->_rateHlp->myEt(),
                [])
            ->where('rt.review_id is null');
        $readyStatuses = $this->_scopeConfig->getValue('udropship/vendor_rating/ready_status', ScopeInterface::SCOPE_STORE);
        if (!is_array($readyStatuses)) {
            $readyStatuses = explode(',', $readyStatuses);
        }
        if (empty($readyStatuses)) {
            $this->getSelect()->where('false');
        } else {
            $this->getSelect()->where('main_table.udropship_status in (?)', $readyStatuses);
        }
        return $this;
    }
    public function addNotificationDaysFilter($filter)
    {
        if (!is_array($filter)) {
            $filter = explode(',', $filter);
        }
        $conn = $this->getSelect()->getAdapter();
        $cases = [];
        $now = $this->_hlp->now();
        $idx=0; foreach ($filter as $fPart) {
            $cases[$idx] = $conn->quoteInto('DATE_ADD(main_table.udrating_date, interval '.(int)$fPart.' DAY)<?', $now);
            $idx++;
        }
        $this->getSelect()->where(
            $this->_rateHlp->getCaseSql('main_table.udrating_emails_sent', $cases, 'false')
        );
        return $this;
    }
    public function getCustomerIds()
    {
        $clonedSelect = clone $this->getSelect();
        $clonedSelect->reset(Select::COLUMNS);
        $clonedSelect->columns('main_table.customer_id')->distinct(true);
        $customerIds = $clonedSelect->getAdapter()->fetchCol($clonedSelect);
        return $customerIds;
    }
    public function joinReviews()
    {
        $reviewTable = $this->_hlp->rHlp()->getTableName('review');
        $reviewDetailTable = $this->_hlp->rHlp()->getTableName('review_detail');
        $this->getSelect()
            ->join(['rt' => $reviewTable],
                'rt.rel_entity_pk_value = main_table.entity_id and rt.entity_id='.$this->_rateHlp->myEt(),
                ['review_id', 'created_at', 'entity_pk_value', 'rel_entity_pk_value', 'status_id'])
            ->join(['rdt' => $reviewDetailTable], 'rdt.review_id = rt.review_id');
        return $this;
    }
    public function joinShipmentItemData()
    {
        $this->getSelect()
            ->join(['ssi' => $this->getTable('sales_shipment_item')], 'ssi.parent_id = main_table.entity_id',[''])
            ->join(['soi' => $this->getTable('sales_order_item')], 'ssi.order_item_id = soi.item_id',[''])
            ->where('soi.parent_item_id is null')
            ->group('main_table.entity_id');
        $this->getSelect()->columns([
            'product_name_list'=>new \Zend_Db_Expr("group_concat(soi.name separator '\n')"),
            'product_sku_list'=>new \Zend_Db_Expr("group_concat(soi.sku separator '\n')"),
        ]);
    }
    public function addStoreFilter($storeId=null)
    {
        $this->getSelect()
            ->join(['store'=>$this->_reviewStoreTable],
                'rt.review_id=store.review_id AND store.store_id=' . (int)$storeId, []);
        return $this;
    }
    public function setStoreFilter($storeId)
    {
        if( is_array($storeId) && isset($storeId['eq']) ) {
            $storeId = array_shift($storeId);
        }

        if( is_array($storeId) ) {
            $this->getSelect()
                ->join(['store'=>$this->_reviewStoreTable],
                    $this->getConnection()->quoteInto('rt.review_id=store.review_id AND store.store_id IN(?)', $storeId), [])
                ->distinct(true)
                ;
        } else {
            $this->getSelect()
                ->join(['store'=>$this->_reviewStoreTable],
                    'rt.review_id=store.review_id AND store.store_id=' . (int)$storeId, []);
        }

        return $this;
    }
    public function addStoreData()
    {
        $this->_addStoreDataFlag = true;
        return $this;
    }
    public function addEntityFilter($entityId)
    {
        $this->getSelect()
            ->where('rt.entity_pk_value = ?', $entityId);
        return $this;
    }

    public function addStatusFilter($status)
    {
        $this->getSelect()
            ->where('rt.status_id = ?', $status);
        return $this;
    }

    public function setDateOrder($dir='DESC')
    {
        $this->setOrder('rt.created_at', $dir);
        return $this;
    }

    public function addReviewSummary()
    {
        foreach( $this->getItems() as $item ) {
            $model = $this->_ratingFactory->create();
            $model->getReviewSummary($item->getReviewId());
            $item->addData($model->getData());
        }
        return $this;
    }

    public function addRateVotes()
    {
        foreach( $this->getItems() as $item ) {
            $votesCollection = $this->_optionVoteFactory->create()
                ->getResourceCollection()
                ->setEntityPkFilter($item->getEntityId())
                ->setStoreFilter($this->_storeManager->getStore()->getId())
                ->load();
            $item->setRatingVotes( $votesCollection );
        }
        return $this;
    }

    public function setOrder($attribute, $dir='desc')
    {
        switch( $attribute ) {
            case 'rt.review_id':
            case 'rt.created_at':
            case 'rt.status_id':
            case 'rdt.title':
            case 'rdt.nickname':
            case 'rdt.detail':
                $this->getSelect()->order($attribute . ' ' . $dir);
                break;
            case 'stores':
                // No way to sort
                break;
            case 'type':
                $this->getSelect()->order('rdt.customer_id ' . $dir);
                break;
            default:
                parent::setOrder($attribute, $dir);
        }
        return $this;
    }

    protected $_addStoreDataFlag;
    protected function _afterLoad()
    {
        parent::_afterLoad();
        if ($this->_addStoreDataFlag) {
            $this->_addStoreData();
        }
        return $this;
    }

    protected function _addStoreData()
    {
        $reviewsIds = $this->getColumnValues('review_id');
        $storesToReviews = [];
        if (count($reviewsIds)>0) {
            $select = $this->getConnection()->select()
                ->from($this->_reviewStoreTable)
                ->where('review_id IN(?)', $reviewsIds)
                ->where('store_id > ?', 0);
            $result = $this->getConnection()->fetchAll($select);
            foreach ($result as $row) {
                if (!isset($storesToReviews[$row['review_id']])) {
                    $storesToReviews[$row['review_id']] = [];
                }
                $storesToReviews[$row['review_id']][] = $row['store_id'];
            }
        }

        foreach ($this as $item) {
            if(isset($storesToReviews[$item->getReviewId()])) {
                $item->setData('stores',$storesToReviews[$item->getReviewId()]);
            } else {
                $item->setData('stores', []);
            }

        }
    }
}