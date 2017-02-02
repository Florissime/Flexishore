<?php

namespace Unirgy\DropshipVendorRatings\Model\ResourceModel\Review;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Review\Helper\Data as HelperData;
use Magento\Review\Model\Rating\Option\VoteFactory;
use Magento\Review\Model\ResourceModel\Review\Collection as ReviewCollection;
use Magento\Sales\Model\Order\AddressFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Collection extends ReviewCollection
{
    /**
     * @var AddressFactory
     */
    protected $_orderAddressFactory;

    protected $_hlp;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        AddressFactory $orderAddressFactory,
        EntityFactory $entityFactory,
        LoggerInterface $logger, 
        FetchStrategyInterface $fetchStrategy, 
        ManagerInterface $eventManager, 
        HelperData $reviewData, 
        VoteFactory $voteFactory, 
        StoreManagerInterface $storeManager, 
        AdapterInterface $connection = null,
        AbstractDb $resource = null)
    {
        $this->_hlp = $udropshipHelper;
        $this->_orderAddressFactory = $orderAddressFactory;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $reviewData, $voteFactory, $storeManager, $connection, $resource);
    }

    protected function _beforeLoad()
    {
        return $this;
    }
    protected function _afterLoad()
    {
        parent::_afterLoad();
        if ($this->getFlag('AddRateVotes')) {
            $this->addRateVotes();
        }
        if ($this->getFlag('AddAddressData')) {
            $this->addAddressData();
        }
        return $this;
    }
    public function addAddressData()
    {
        $rIdsBySid = $sIds = [];
        foreach ($this->getItems() as $item) {
            $sId = $item->getData('rel_entity_pk_value');
            $sIds[] = $sId;
            $rIdsBySid[$sId] = $item->getId();
        }
        $rHlp = $this->_hlp->rHlp();
        $saIds = [];
        $saIdsData = $rHlp->loadDbColumns($this->_hlp->createObj('Magento\Sales\Model\Order\Shipment'), $sIds, ['shipping_address_id']);
        foreach ($saIdsData as $sId=>$saIdData) {
            $saId = $saIdData['shipping_address_id'];
            $saIds[$sId] = $saId;
        }
        $addrCol = $this->_orderAddressFactory->create()->getCollection();
        if (!empty($saIds)) {
            $addrCol->addFieldToFilter('entity_id', ['in'=>$saIds]);
        } else {
            $addrCol->addFieldToFilter('entity_id', false);
        }
        foreach ($rIdsBySid as $sId=>$rId) {
            $saId = @$saIds[$sId];
            if ($saId && ($sa = $addrCol->getItemById($saId)) && ($r = $this->getItemById($rId))) {
                $r->setShippingAddress($sa);
            }
        }
    }
}