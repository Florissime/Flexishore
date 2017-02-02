<?php

namespace Unirgy\Dropship\Controller\Vendor;

class Pdf extends AbstractVendor
{
    /**
    * Download one packing slip
    *
    */
    public function execute()
    {
        try {
            $id = $this->getRequest()->getParam('shipment_id');
            if (!$id) {
                throw new \Exception('Invalid shipment ID is supplied');
            }

            $shipments = $this->_hlp->createObj('\Magento\Sales\Model\ResourceModel\Order\Shipment\Collection')
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', $id)
                ->load();
            if (!$shipments->getSize()) {
                throw new \Exception(__('No shipments found with supplied IDs'));
            }

            $this->_preparePackingSlips($shipments);

        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        return $this->resultRedirectFactory->create()->setPath('udropship/vendor/');
    }
}
