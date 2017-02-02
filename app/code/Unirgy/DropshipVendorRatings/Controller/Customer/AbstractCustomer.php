<?php

namespace Unirgy\DropshipVendorRatings\Controller\Customer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Json\EncoderInterface;
use Magento\Sales\Model\Order\ShipmentFactory;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;
use Magento\Framework\App\RequestInterface;

abstract class AbstractCustomer extends Action
{
    /**
     * @var HelperData
     */
    protected $_rateHlp;

    protected $_hlp;

    public function __construct(
        Context $context,
        HelperData $helperData,
        \Unirgy\Dropship\Helper\Data $udropshipHelper
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_rateHlp = $helperData;

        parent::__construct($context);
    }

    protected function _saveFormData($data=null, $id=null)
    {
        $this->_rateHlp->saveFormData($data, $id);
    }

    protected function _fetchFormData($id=null)
    {
        return $this->_rateHlp->fetchFormData($id);
    }

    protected function _getSession()
    {
        return ObjectManager::getInstance()->get('Magento\Customer\Model\Session');
    }

    public function dispatch(RequestInterface $request)
    {
        if (!$this->_getSession()->authenticate()) {
            $this->_actionFlag->set('', 'no-dispatch', true);
        }
        return parent::dispatch($request);
    }

    protected function _validatePost()
    {
        $id = $this->getRequest()->getParam('id');
        $relId = $this->getRequest()->getParam('rel_id');
        $customerId = $this->_getSession()->getCustomerId();
        $shipment = $this->_hlp->createObj('Magento\Sales\Model\Order\Shipment')->load($relId);
        return !empty($id) && !empty($relId) && $shipment->getId()
               && $shipment->getUdropshipVendor()===$id && $shipment->getCustomerId()==$customerId;
    }

    public function returnResult($result)
    {
        return $this->resultFactory->create(ResultFactory::TYPE_RAW)->setContents($this->_hlp->jsonEncode($result));
    }
}
