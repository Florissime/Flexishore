<?php

namespace Unirgy\Dropship\Controller\Vendor;

class LabelBatch extends AbstractVendor
{
    /**
    * Generate and print labels batch
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

            $this->_hlp->createObj('\Unirgy\Dropship\Model\Label\Batch')
                ->setVendor($this->_hlp->session()->getVendor())
                ->processShipments($shipments, array(), array('mark_shipped'=>true))
                ->prepareLabelsDownloadResponse();

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
