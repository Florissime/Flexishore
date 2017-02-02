<?php

namespace Unirgy\DropshipMicrosite\Controller\Adminhtml\Registration;

class MassDelete extends AbstractRegistration
{
    public function execute()
    {
        $registrationIds = $this->getRequest()->getParam('vendor');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!is_array($registrationIds)) {
            $this->messageManager->addError(__('Please select registration(s)'));
        }
        else {
            try {
                /** @var \Unirgy\DropshipMicrosite\Model\Registration $registration */
                $registration = $this->_hlp->createObj('\Unirgy\DropshipMicrosite\Model\Registration');
                foreach ($registrationIds as $registrationId) {
                    $registration->setId($registrationId)->delete();
                }
                $this->messageManager->addSuccess(
                    __('Total of %1 record(s) were successfully deleted', count($registrationIds))
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/index');
    }
}
