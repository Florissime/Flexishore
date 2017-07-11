<?php

namespace Flexishore\Base\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\DB\Select;

class CatalogBlockProductListCollection implements ObserverInterface
{
    /**
    * Add products collections vendor data
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        /*
        print_r(' fCatalogBlockProductListCollection ');
        $collection = $observer->getEvent()->getCollection();

        $alreadyJoined = false;
        foreach ($collection->getSelect()->getPart(Select::COLUMNS) as $column) {
            if ($column[2]=='vendor_name' || $column[2]=='vendor_city') {
                $alreadyJoined = true;
                break;
            }
        }
        if (!$alreadyJoined) {
            print_r(' fCatalogBlockProductListCollectionJoin ');
            //$collection->joinAttribute('udropship_vendor', 'catalog_product/udropship_vendor', 'entity_id', null, 'left');
            $collection->joinField('vendor_name', 'udropship_vendor', 'vendor_name', 'vendor_id=udropship_vendor', [], 'left');
            $collection->joinField('vendor_city', 'udropship_vendor', 'city', 'vendor_id=udropship_vendor', [], 'left');
        }

        $collection->printlogquery(true);*/

        return $this;
    }
}
