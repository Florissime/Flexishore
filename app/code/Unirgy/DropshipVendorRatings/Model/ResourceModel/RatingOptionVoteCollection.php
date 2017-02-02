<?php

namespace Unirgy\DropshipVendorRatings\Model\ResourceModel;

use Magento\Review\Model\ResourceModel\Rating\Option\Vote\Collection;
use Unirgy\Dropship\Helper\Data as HelperData;

class RatingOptionVoteCollection extends Collection
{
    public function addRatingInfo($storeId=null)
    {
        $result = parent::addRatingInfo($storeId);
        $this->getSelect()->columns(['is_aggregate'=>'rating.is_aggregate']);
        return $result;
    }
}