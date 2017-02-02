<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class AdminhtmlCatalogProductEditPrepareForm extends AbstractObserver implements ObserverInterface
{

    /**
    * Remove Dropship Vendor dropdown when editing products if logged in as a vendor
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        if ($v = $this->_getVendor()) {
            $form = $observer->getEvent()->getForm();
            $hideFields = explode(',', $this->scopeConfig->getValue('udropship/microsite/hide_product_attributes', ScopeInterface::SCOPE_STORE));
            $hideFields[] = 'udropship_vendor';
            foreach ($form->getElements() as $fieldset) {
                foreach ($fieldset->getElements() as $field) {
                    if (in_array($field->getId(), $hideFields)) {
                        $fieldset->removeField($field->getId());
                    }
                }
            }
        }
    }
}
