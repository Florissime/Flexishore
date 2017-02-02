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

namespace Unirgy\Dropship\Controller\Adminhtml\Shipment;

use \Magento\Backend\App\Action;
use \Magento\Backend\App\Action\Context;
use \Magento\Framework\Controller\Result\RedirectFactory;
use \Magento\Framework\Registry;
use \Magento\Sales\Model\Order\Shipment;

abstract class AbstractShipment extends Action
{
    /**
     * @var Shipment
     */
    protected $_orderShipment;

    /**
     * @var RedirectFactory
     */
    protected $_resultRedirectFactory;

    /**
     * @var Registry
     */
    protected $_frameworkRegistry;

    public function __construct(Context $context, 
        Shipment $orderShipment, 
        RedirectFactory $resultRedirectFactory, 
        Registry $frameworkRegistry)
    {
        $this->_orderShipment = $orderShipment;
        $this->_resultRedirectFactory = $resultRedirectFactory;
        $this->_frameworkRegistry = $frameworkRegistry;

        parent::__construct($context);
    }

    
    protected function _initShipment()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        $shipment = $this->_orderShipment->load($shipmentId);
        if (!$shipment->getId()) {
            $this->messageManager->addError(__('This shipment no longer exists.'));
            $this->_resultRedirectFactory->create()->setPath('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        $this->_frameworkRegistry->register('current_shipment', $shipment);

        return $shipment;
    }
    


    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('sales/shipment');
    }
    
}
