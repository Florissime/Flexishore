<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Shipment;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\Controller\Result\RedirectFactory;
use \Magento\Framework\Exception;
use \Magento\Framework\Registry;
use \Magento\Sales\Model\Order\Shipment;
use \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use \Psr\Log\LoggerInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;

class ResendPo extends AbstractShipment
{
    /**
     * @var Collection
     */
    protected $_shipmentCollection;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var LoggerInterface
     */
    protected $_logLoggerInterface;

    public function __construct(Context $context, 
        Shipment $orderShipment, 
        RedirectFactory $resultRedirectFactory, 
        Registry $frameworkRegistry, 
        Collection $shipmentCollection, 
        HelperData $helperData, 
        LoggerInterface $logLoggerInterface)
    {
        $this->_shipmentCollection = $shipmentCollection;
        $this->_helperData = $helperData;
        $this->_logLoggerInterface = $logLoggerInterface;

        parent::__construct($context, $orderShipment, $resultRedirectFactory, $frameworkRegistry);
    }

    public function execute(){
        $poIds = $this->getRequest()->getPost('shipment_ids');
        if (!empty($poIds)) {
            try {
                $pos = $this->_shipmentCollection
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('entity_id', array('in' => $poIds))
                    ->load();

                foreach ($pos as $po) {
                    $po->afterLoad();
                    $po->setResendNotificationFlag(true);
                    $this->_helperData->sendVendorNotification($po);
                }

                $this->messageManager->addSuccess(__('%1 notifications sent.', count($poIds)));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->_logLoggerInterface->error($e);
                $this->messageManager->addError(__('Problems during notifications resend.'));
            }
        }
        return $this->_resultRedirectFactory->create()->setPath('sales/shipment/index');
    }
}
