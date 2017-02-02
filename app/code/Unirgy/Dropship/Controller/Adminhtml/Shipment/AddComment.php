<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Shipment;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\Controller\Result\RawFactory;
use \Magento\Framework\Controller\Result\RedirectFactory;
use \Magento\Framework\Exception;
use \Magento\Framework\Registry;
use \Magento\Framework\View\LayoutFactory;
use \Magento\Sales\Model\Order\Shipment;
use \Magento\Sales\Model\Order\Shipment\Comment;
use \Psr\Log\LoggerInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Model\Source;

class AddComment extends AbstractShipment
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var Comment
     */
    protected $_shipmentComment;

    /**
     * @var LayoutFactory
     */
    protected $_viewLayoutFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logLoggerInterface;

    /**
     * @var RawFactory
     */
    protected $_resultRawFactory;

    public function __construct(Context $context, 
        Shipment $orderShipment, 
        RedirectFactory $resultRedirectFactory, 
        Registry $frameworkRegistry, 
        HelperData $helperData, 
        Comment $shipmentComment,
        LayoutFactory $viewLayoutFactory, 
        LoggerInterface $logLoggerInterface, 
        RawFactory $resultRawFactory)
    {
        $this->_hlp = $helperData;
        $this->_shipmentComment = $shipmentComment;
        $this->_viewLayoutFactory = $viewLayoutFactory;
        $this->_logLoggerInterface = $logLoggerInterface;
        $this->_resultRawFactory = $resultRawFactory;

        parent::__construct($context, $orderShipment, $resultRedirectFactory, $frameworkRegistry);
    }

    public function execute()
    {
        try {
            $data = $this->getRequest()->getPost('comment');
            $shipment = $this->_initShipment();
            if (empty($data['comment']) && $data['status']==$shipment->getUdropshipStatus()) {
                throw new \Exception(__('Comment text field cannot be empty.'));
            }

            $hlp = $this->_hlp;
            $status = $data['status'];
            
            $statusShipped   = Source::SHIPMENT_STATUS_SHIPPED;
            $statusDelivered = Source::SHIPMENT_STATUS_DELIVERED;
            $statusCanceled  = Source::SHIPMENT_STATUS_CANCELED;
            $statuses = $this->_hlp->src()->setPath('shipment_statuses')->toOptionHash();

            /** @var \Magento\Backend\Model\Auth $auth */
            $auth = $this->_hlp->getObj('\Magento\Backend\Model\Auth');
            $adminUser = $auth->getUser();

            $statusSaveRes = true;
            if ($status!=$shipment->getUdropshipStatus()) {
                $oldStatus = $shipment->getUdropshipStatus();
                if (($oldStatus==$statusShipped || $oldStatus==$statusDelivered) 
                    && $status!=$statusShipped && $status!=$statusDelivered && $hlp->isUdpoActive()
                ) {
                    $this->_hlp->udpoHlp()->revertCompleteShipment($shipment, true);
                } elseif ($oldStatus==$statusCanceled && $hlp->isUdpoActive()) {
                    throw new \Exception(__('Canceled shipment cannot be reverted'));
                }
                $changedComment = __("%1\n\n[%2 has changed the shipment status to %3]", $data['comment'], 'Administrator', $statuses[$status]);
                $triedToChangeComment = __("%1\n\n[%2 tried to change the shipment status to %3]", $data['comment'], 'Administrator', $statuses[$status]);
                if ($status==$statusShipped || $status==$statusDelivered) {
                    $hlp->completeShipment($shipment, true, $status==$statusDelivered);
                    $hlp->completeOrderIfShipped($shipment, true);
                    $hlp->completeUdpoIfShipped($shipment, true);
                    $commentText = $changedComment;
                } elseif ($status == $statusCanceled && $hlp->isUdpoActive()) {
                    if ($this->_hlp->udpoHlp()->cancelShipment($shipment, true)) {
                        $commentText = $changedComment;
                        $this->_hlp->udpoHlp()->processPoStatusSave($this->_hlp->udpoHlp()->getShipmentPo($shipment), constant('\Unirgy\DropshipPo\Model\Source::UDPO_STATUS_PARTIAL'), true, null);
                    } else {
                        $commentText = $triedToChangeComment;
                    }
                } else {
                    $shipment->setUdropshipStatus($status)->save();
                    $commentText = $changedComment;
                }
                $comment = $this->_shipmentComment
                    ->setComment($commentText)
                    ->setIsCustomerNotified(isset($data['is_customer_notified']))
                    ->setIsVendorNotified(isset($data['is_vendor_notified']))
                    ->setIsVisibleToVendor(isset($data['is_visible_to_vendor']))
                    ->setUsername($adminUser->getUsername())
                    ->setUdropshipStatus(@$statuses[$status]);
                $shipment->addComment($comment);
                if (isset($data['is_vendor_notified'])) {
                    $this->_hlp->sendShipmentCommentNotificationEmail($shipment, $data['comment']);
                }
                $shipment->sendUpdateEmail(!empty($data['is_customer_notified']), $data['comment']);
                $shipment->getCommentsCollection()->save();
            } else {
                $comment = $this->_shipmentComment
                    ->setComment($data['comment'])
                    ->setIsCustomerNotified(isset($data['is_customer_notified']))
                    ->setIsVendorNotified(isset($data['is_vendor_notified']))
                    ->setIsVisibleToVendor(isset($data['is_visible_to_vendor']))
                    ->setUsername($adminUser->getUsername())
                    ->setUdropshipStatus(@$statuses[$status]);
                $shipment->addComment($comment);
                if (isset($data['is_vendor_notified'])) {
                    $this->_hlp->sendShipmentCommentNotificationEmail($shipment, $data['comment']);
                }
                $shipment->sendUpdateEmail(!empty($data['is_customer_notified']), $data['comment']);
                $shipment->getCommentsCollection()->save();
            }

            $this->loadLayout();
            $response = $this->_viewLayoutFactory->create()->getBlock('order_comments')->toHtml();
        } catch (\Exception $e) {
            $response = array(
                'error'     => true,
                'message'   => $e->getMessage()
            );
            $response = \Zend_Json::encode($response);
        } catch (\Exception $e) {
            $this->_logLoggerInterface->error($e);
            $response = array(
                'error'     => true,
                'message'   => __('Cannot add new comment.')
            );
            $response = \Zend_Json::encode($response);
        }
        $this->_resultRawFactory->create()->setContents($response);
    }
}
