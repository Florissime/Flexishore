<?php

namespace Unirgy\DropshipShippingClass\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipShippingClass\Model\Source;

class UdropshipAdminhtmlShippingGridPrepareColumns extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $grid = $observer->getGrid();
        $grid->addColumn('customer_ship_class', [
            'header'        => __('Customer Ship Class'),
            'index'         => 'customer_ship_class',
            'type'          => 'options',
            'options'       => $this->_modelSource->setPath('customer_ship_class')->toOptionHash(),
            'sortable'      => false,
            'filter'        => false,
        ]);
        $grid->addColumn('vendor_ship_class', [
            'header'        => __('Vendor Ship Class'),
            'index'         => 'vendor_ship_class',
            'type'          => 'options',
            'options'       => $this->_modelSource->setPath('vendor_ship_class')->toOptionHash(),
            'sortable'      => false,
            'filter'        => false,
        ]);
    }
}
