<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Shipping;

class MassDelete extends AbstractShipping
{
    public function execute()
    {
        $shippingIds = $this->getRequest()->getParam('shipping');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!is_array($shippingIds)) {
            $this->messageManager->addError(__('Please select shipping method(s)'));
        }
        else {
            try {
                $shipping = $this->_hlp->createObj('\Unirgy\Dropship\Model\Shipping');
                foreach ($shippingIds as $shippingId) {
                    $shipping->setId($shippingId)->delete();
                }
                $this->messageManager->addSuccess(
                    __('Total of %1 record(s) were successfully deleted', count($shippingIds))
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/index');
    }
}
