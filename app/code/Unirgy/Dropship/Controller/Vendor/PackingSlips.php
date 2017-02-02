<?php

namespace Unirgy\Dropship\Controller\Vendor;

class PackingSlips extends AbstractVendor
{
    /**
    * Download multiple packing slips
    *
    */
    public function execute()
    {
    	$result = array();
        try {
            $shipments = $this->getVendorShipmentCollection();
            if (!$shipments->getSize()) {
                throw new \Exception(__('No shipments found for these criteria'));
            }

            return $this->_preparePackingSlips($shipments);

        } catch (\Exception $e) {
        	if ($this->getRequest()->getParam('use_json_response')) {
        		$result = array(
        			'error'=>true,
        			'message'=>$e->getMessage()
        		);
        	} else {
                $this->messageManager->addError(__($e->getMessage()));
        	}
        }
    	if ($this->getRequest()->getParam('use_json_response')) {
        	return $this->_resultRawFactory->create()->setContents(
        		$this->_hlp->jsonEncode($result)
        	);
        } else {
        	return $this->resultRedirectFactory->create()->setPath('udropship/vendor/', array('_current'=>true, '_query'=>array('submit_action'=>'')));
        }
    }
}
