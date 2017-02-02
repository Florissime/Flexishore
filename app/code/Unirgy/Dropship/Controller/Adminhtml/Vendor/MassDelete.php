<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;

class MassDelete extends AbstractVendor
{
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $certIds = $this->getRequest()->getParam('vendor');
        if (!is_array($certIds)) {
            $this->messageManager->addError(__('Please select vendor(s)'));
        }
        else {
            try {
                /** @var \Unirgy\Dropship\Model\Vendor $cert */
                $cert = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor');
                foreach ($certIds as $certId) {
                    $cert->setId($certId)->delete();
                }
                $this->messageManager->addSuccess(
                    __('Total of %1 record(s) were successfully deleted', count($certIds))
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/index');
    }
}
