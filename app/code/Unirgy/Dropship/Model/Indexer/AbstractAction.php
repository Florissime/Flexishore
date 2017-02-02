<?php

namespace Unirgy\Dropship\Model\Indexer;

class AbstractAction
{
    protected $_resource;
    public function __construct(
        \Unirgy\Dropship\Model\ResourceModel\Indexer\VendorProductAssoc $resource
    )
    {
        $this->_resource = $resource;
    }

}