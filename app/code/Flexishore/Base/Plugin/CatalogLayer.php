<?php

namespace Flexishore\Base\Plugin;

use Magento\Framework\DB\Select;

class CatalogLayer
{
    public function beforePrepareProductCollection(\Magento\Catalog\Model\Layer\Category $subject, $collection)
    {
        $alreadyJoined = false;
        foreach ($collection->getSelect()->getPart(Select::COLUMNS) as $column) {
            if ($column[2]=='vendor_name' || $column[2]=='vendor_city') {
                $alreadyJoined = true;
                break;
            }
        }
        if (!$alreadyJoined) {
            $collection->joinField('vendor_name', 'udropship_vendor', 'vendor_name', 'vendor_id=udropship_vendor', [], 'left');
            $collection->joinField('vendor_city', 'udropship_vendor', 'city', 'vendor_id=udropship_vendor', [], 'left');
        }
    }
}