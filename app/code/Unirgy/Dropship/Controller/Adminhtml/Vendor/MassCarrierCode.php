<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;

use \Unirgy\Dropship\Model\Vendor;

class MassCarrierCode extends AbstractVendor
{
    public function execute()
    {
        $modelIds = (array)$this->getRequest()->getParam('vendor');
        $carrier_code     = (string)$this->getRequest()->getParam('carrier_code');

        try {
            foreach ($modelIds as $modelId) {
                $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor')->load($modelId)->setCarrierCode($carrier_code)->save();
            }
            $this->messageManager->addSuccess(
                __('Total of %1 record(s) were successfully updated', count($modelIds))
            );
        }
        catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        }
        catch (\Exception $e) {
            $this->messageManager->addException($e, __('There was an error while updating vendor(s) preferred carrier'));
        }

        return $this->_resultRedirectFactory->create()->setPath('*/*/');
    }
}
