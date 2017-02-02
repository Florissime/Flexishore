<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Shipment;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\Controller\Result\RedirectFactory;
use \Magento\Framework\Registry;
use \Magento\Sales\Model\Order\Shipment;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Ship extends AbstractShipment
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    public function __construct(Context $context, 
        Shipment $orderShipment, 
        RedirectFactory $resultRedirectFactory, 
        Registry $frameworkRegistry, 
        HelperData $helperData)
    {
        $this->_helperData = $helperData;

        parent::__construct($context, $orderShipment, $resultRedirectFactory, $frameworkRegistry);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $shipment = $this->_orderShipment->load($id);
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($shipment->getId()) {
            try {

                $this->_helperData->setShipmentComplete($shipment);
                $this->messageManager->addSuccess(__('Shipment has been marked as complete'));
            } catch (\Exception $e) {
                $this->messageManager->addError(__('There was a problem marking this shipment as complete: '.$e->getMessage()));
            }
        } else {
            $this->messageManager->addError(__('Invalid shipment ID supplied'));
        }

        $orderId = $this->getRequest()->getParam('order_id');
        return $resultRedirect->setPath("sales/order_shipment/view/shipment_id/$id/order_id/$orderId");
    }
}
