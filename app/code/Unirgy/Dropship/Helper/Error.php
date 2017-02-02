<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    \Unirgy\Dropship
 * @copyright  Copyright (c) 2015-2016 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\Dropship\Helper;

use \Magento\Backend\Model\Url;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Store\Model\StoreManagerInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Error extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Url
     */
    protected $_backendUrl;

    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Url $backendUrl,
        HelperData $helperData
    )
    {
        $this->_storeManager = $storeManager;
        $this->_backendUrl = $backendUrl;
        $this->_hlp = $helperData;

        parent::__construct($context);
    }

    public function sendPollTrackingFailedNotification($tracks, $error, $storeId)
    {
        $store = $this->_storeManager->getStore($storeId);

        if (!$this->_hlp->getScopeConfig('udropship/error_notifications/enabled', $store) || empty($tracks)) {
            return $this;
        }

        $subject  = $this->_hlp->getScopeConfig('udropship/error_notifications/poll_tracking_failed_subject', $store);
        $template = $this->_hlp->getScopeConfig('udropship/error_notifications/poll_tracking_failed_template', $store);
        $to       = $this->_hlp->getScopeConfig('udropship/error_notifications/receiver', $store);
        $from     = $this->_hlp->getScopeConfig('udropship/error_notifications/sender', $store);

        $trackingIds = array();
        $orderIds = array();
        $shipmentIds = array();
        foreach ($tracks as $track) {
            $trackingIds[$track->getNumber()] = $track->getNumber();
            $shipmentIds[$track->getShipment()->getIncrementId()] = $track->getShipment()->getIncrementId();
            $orderIds[$track->getShipment()->getOrder()->getIncrementId()] = $track->getShipment()->getOrder()->getIncrementId();
        }

        $ahlp = $this->_backendUrl;

        if ($subject && $template) {
            $toEmail = $this->_hlp->getScopeConfig('trans_email/ident_'.$to.'/email', $store);
            $toName = $this->_hlp->getScopeConfig('trans_email/ident_'.$to.'/name', $store);
            $fromEmail = $this->_hlp->getScopeConfig('trans_email/ident_'.$from.'/email', $store);
            $fromName = $this->_hlp->getScopeConfig('trans_email/ident_'.$from.'/name', $store);
            $data = array(
                'tracking_ids'  => implode("\n", $trackingIds),
                'order_ids'     => implode("\n", $orderIds),
                'shipment_ids'  => implode("\n", $shipmentIds),
                'error'         => $error,
            );
            foreach ($data as $k=>$v) {
                $subject = str_replace('{{'.$k.'}}', $v, $subject);
                $template = str_replace('{{'.$k.'}}', $v, $template);
            }

            /** @var \Magento\Framework\Mail\Message $message */
            $message = $this->_hlp->createObj('Magento\Framework\Mail\Message');
            $message->setMessageType(\Magento\Framework\Mail\MessageInterface::TYPE_TEXT)
                ->setFrom($fromEmail, $fromName)
                ->addTo($toEmail, $toName)
                ->setSubject($subject)
                ->setBodyText($template)
                ->send();
            $transport = $this->_hlp->createObj('Magento\Framework\Mail\TransportInterface', ['message' => $message]);
            $transport->sendMessage();
        }
    }
    public function sendPollTrackingLimitExceededNotification($tracks, $storeId)
    {
        $store = $this->_storeManager->getStore($storeId);

        if (!$this->_hlp->getScopeConfig('udropship/error_notifications/enabled', $store) || empty($tracks)) {
            return $this;
        }

        $limit    = $this->_hlp->getScopeConfig('udropship/error_notifications/poll_tracking_limit', $store);
        $subject  = $this->_hlp->getScopeConfig('udropship/error_notifications/poll_tracking_limit_exceeded_subject', $store);
        $template = $this->_hlp->getScopeConfig('udropship/error_notifications/poll_tracking_limit_exceeded_template', $store);
        $to       = $this->_hlp->getScopeConfig('udropship/error_notifications/receiver', $store);
        $from     = $this->_hlp->getScopeConfig('udropship/error_notifications/sender', $store);

        $trackingIds = array();
        $orderIds = array();
        $shipmentIds = array();
        foreach ($tracks as $track) {
            $trackingIds[$track->getNumber()] = $track->getNumber();
            $shipmentIds[$track->getShipment()->getIncrementId()] = $track->getShipment()->getIncrementId();
            $orderIds[$track->getShipment()->getOrder()->getIncrementId()] = $track->getShipment()->getOrder()->getIncrementId();
        }

        $ahlp = $this->_backendUrl;

        if ($subject && $template) {
            $toEmail = $this->_hlp->getScopeConfig('trans_email/ident_'.$to.'/email', $store);
            $toName = $this->_hlp->getScopeConfig('trans_email/ident_'.$to.'/name', $store);
            $fromEmail = $this->_hlp->getScopeConfig('trans_email/ident_'.$from.'/email', $store);
            $fromName = $this->_hlp->getScopeConfig('trans_email/ident_'.$from.'/name', $store);
            $data = array(
                'tracking_ids'  => implode("\n", $trackingIds),
                'order_ids'     => implode("\n", $orderIds),
                'shipment_ids'  => implode("\n", $shipmentIds),
                'limit'         => $limit,
            );
            foreach ($data as $k=>$v) {
                $subject = str_replace('{{'.$k.'}}', $v, $subject);
                $template = str_replace('{{'.$k.'}}', $v, $template);
            }

            /** @var \Magento\Framework\Mail\Message $message */
            $message = $this->_hlp->createObj('Magento\Framework\Mail\Message');
            $message->setMessageType(\Magento\Framework\Mail\MessageInterface::TYPE_TEXT)
                ->setFrom($fromEmail, $fromName)
                ->addTo($toEmail, $toName)
                ->setSubject($subject)
                ->setBodyText($template)
                ->send();
            $transport = $this->_hlp->createObj('Magento\Framework\Mail\TransportInterface', ['message' => $message]);
            $transport->sendMessage();
        }

        return $this;
    }
    public function sendLabelRequestFailedNotification($shipment, $error)
    {
        $order = $shipment->getOrder();
        $store = $order->getStore();

        if (!$this->_hlp->getScopeConfig('udropship/error_notifications/enabled', $store)) {
            return $this;
        }

        $subject  = $this->_hlp->getScopeConfig('udropship/error_notifications/label_request_failed_subject', $store);
        $template = $this->_hlp->getScopeConfig('udropship/error_notifications/label_request_failed_template', $store);
        $to       = $this->_hlp->getScopeConfig('udropship/error_notifications/receiver', $store);
        $from     = $this->_hlp->getScopeConfig('udropship/error_notifications/sender', $store);

        $vendor = $this->_hlp->getVendor($shipment->getUdropshipVendor());
        $ahlp = $this->_backendUrl;

        if ($subject && $template && $vendor->getId()) {
            $toEmail = $this->_hlp->getScopeConfig('trans_email/ident_'.$to.'/email', $store);
            $toName = $this->_hlp->getScopeConfig('trans_email/ident_'.$to.'/name', $store);
            $fromEmail = $this->_hlp->getScopeConfig('trans_email/ident_'.$from.'/email', $store);
            $fromName = $this->_hlp->getScopeConfig('trans_email/ident_'.$from.'/name', $store);
            $data = array(
                'vendor_name'   => $vendor->getVendorName(),
                'order_id'      => $order->getIncrementId(),
                'shipment_id'   => $shipment->getIncrementId(),
                'vendor_url'    => $ahlp->getUrl('udropship/vendor/edit', array(
                    'id'        => $vendor->getId()
                )),
                'order_url'     => $ahlp->getUrl('sales/order/view', array(
                    'order_id'  => $order->getId()
                )),
                'shipment_url'  => $ahlp->getUrl('sales/order_shipment/view', array(
                    'shipment_id'=> $shipment->getId(),
                    'order_id'  => $order->getId(),
                )),
                'error'      => $error,
            );
            if ($this->_hlp->isUdpoActive() && ($po = $this->_hlp->udpoHlp()->getShipmentPo($shipment))) {
                $data['po_id'] = $po->getIncrementId();
                $data['po_url'] = $ahlp->getUrl('udpo/order_po/view', array(
                    'udpo_id'  => $po->getId(),
                    'order_id' => $order->getId(),
                ));
                $template = preg_replace('/{{isPoAvailable}}(.*?){{\/isPoAvailable}}/s', '\1', $template);
            } else {
                $template = preg_replace('/{{isPoAvailable}}.*?{{\/isPoAvailable}}/s', '', $template);
            }
            foreach ($data as $k=>$v) {
                $subject = str_replace('{{'.$k.'}}', $v, $subject);
                $template = str_replace('{{'.$k.'}}', $v, $template);
            }

            /** @var \Magento\Framework\Mail\Message $message */
            $message = $this->_hlp->createObj('Magento\Framework\Mail\Message');
            $message->setMessageType(\Magento\Framework\Mail\MessageInterface::TYPE_TEXT)
                ->setFrom($fromEmail, $fromName)
                ->addTo($toEmail, $toName)
                ->setSubject($subject)
                ->setBodyText($template)
                ->send();
            $transport = $this->_hlp->createObj('Magento\Framework\Mail\TransportInterface', ['message' => $message]);
            $transport->sendMessage();
        }

        return $this;
    }
}
