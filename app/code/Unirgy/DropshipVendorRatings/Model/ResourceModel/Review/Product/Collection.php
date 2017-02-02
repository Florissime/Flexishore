<?php

namespace Unirgy\DropshipVendorRatings\Model\ResourceModel\Review\Product;

use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Rating\Option\VoteFactory;
use Magento\Review\Model\ResourceModel\Review\Product\Collection as ProductCollection;
use Magento\Store\Model\StoreManagerInterface;

class Collection extends ProductCollection
{
    protected function _joinFields()
    {
        $reviewTable = $this->_resource->getTableName('review');
        $reviewDetailTable = $this->_resource->getTableName('review_detail');

        $this->addAttributeToSelect('name')
            ->addAttributeToSelect('sku');

        $this->getSelect()
            ->join(['rt' => $reviewTable],
                'rt.entity_pk_value = e.entity_id and rt.entity_id=1',
                ['review_id', 'created_at', 'entity_pk_value', 'status_id'])
            ->join(['rdt' => $reviewDetailTable], 'rdt.review_id = rt.review_id');
        return $this;
    }
}