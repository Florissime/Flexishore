<?php

namespace Unirgy\DropshipShippingClass\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdropshipAdminhtmlShippingEditPrepareForm extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $id = $observer->getEvent()->getId();
        $form = $observer->getEvent()->getForm();
        $fieldset = $form->getElement('shipping_form');

        $fieldset->addField('vendor_ship_class', 'multiselect', [
            'name' => 'vendor_ship_class',
            'label' => __('Vendor Ship Class'),
            'values' => $this->_modelSource->setPath('vendor_ship_class')->toOptionArray(true),
        ]);
        $fieldset->addField('customer_ship_class', 'multiselect', [
            'name' => 'customer_ship_class',
            'label' => __('Customer Ship Class'),
            'values' => $this->_modelSource->setPath('customer_ship_class')->toOptionArray(true),
        ]);
    }
}
