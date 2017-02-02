<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;

use \Magento\Framework\Model\Exception;
use \Unirgy\Dropship\Model\Vendor;

class MassStatus extends AbstractVendor
{
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $modelIds = (array)$this->getRequest()->getParam('vendor');
        $status     = (string)$this->getRequest()->getParam('status');

        try {
            foreach ($modelIds as $modelId) {
                $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor')->load($modelId)->setStatus($status)->save();
            }
            $this->messageManager->addSuccess(
                __('Total of %1 record(s) were successfully updated', count($modelIds))
            );
        }
        catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        }
        catch (\Exception $e) {
            $this->messageManager->addException($e, __('There was an error while updating vendor(s) status'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
