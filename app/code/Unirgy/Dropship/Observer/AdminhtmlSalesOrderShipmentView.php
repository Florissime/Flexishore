<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Backend\Model\Url;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Observer\AbstractObserver;

class AdminhtmlSalesOrderShipmentView extends AbstractObserver implements ObserverInterface
{
    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var Url
     */
    protected $_backendUrl;

    public function __construct(
        Registry $registry,
        Url $backendUrl,
        \Unirgy\Dropship\Observer\Context $context,
        array $data = []
    )
    {
        $this->_registry = $registry;
        $this->_backendUrl = $backendUrl;

        parent::__construct($context, $data);
    }

    public function execute(Observer $observer)
    {
        if (($soi = Mage::app()->getLayout()->getBlock('order_info'))
            && ($shipment = $this->_registry->registry('current_shipment'))
        ) {
            if (($vName = $this->_hlp->getVendorName($shipment->getUdropshipVendor()))) {
                $soi->setVendorName($vName);
            }
            if (($stId = $shipment->getStatementId())) {
                $soi->setStatementId($stId);
                if (($st = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor\Statement')->load($stId, 'statement_id')) && $st->getId()) {
                    $soi->setStatementUrl($this->_backendUrl->getUrl('udropship/vendor_statement/edit', array('id'=>$st->getId())));
                }
            }
            if ($this->_hlp->isUdpayoutActive() && ($ptId = $shipment->getPayoutId())) {
                $soi->setPayoutId($ptId);
                if (($pt = $this->_hlp->createObj('\Unirgy\DropshipPayout\Model\Payout')->load($ptId)) && $pt->getId()) {
                    $soi->setPayoutUrl($this->_backendUrl->getUrl('udpayout/payout/edit', array('id'=>$pt->getId())));
                }
            }
        	if ($this->_hlp->isUdpoActive() && ($ptId = $shipment->getUdpoId())) {
                $soi->setUdpoId($ptId);
                if ($this->_hlp->udpoHlp()->getShipmentPo($shipment)) {
                	$soi->setUdpoId($shipment->getUdpo()->getIncrementId());
                    $soi->setUdpoUrl($this->_backendUrl->getUrl('udpo/order_po/view', array('udpo_id'=>$shipment->getUdpo()->getId())));
                }
            }
            if ($this->_hlp->isUdropshipOrder($shipment->getOrder())) {
                $shipment->getOrder()->setShippingDescription(sprintf('%s [%s]',
                    $shipment->getOrder()->getShippingDescription(),
                    $shipment->getUdropshipMethodDescription()
                ));
                $shipment->getOrder()->setBaseShippingAmount($shipment->getBaseShippingAmount());
                $shipment->getOrder()->setShippingAmount($shipment->getShippingAmount());
            }
        }
    }
}
