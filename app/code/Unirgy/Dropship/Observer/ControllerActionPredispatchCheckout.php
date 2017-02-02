<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Checkout\Model\Type\Onepage;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class ControllerActionPredispatchCheckout extends AbstractObserver implements ObserverInterface
{
    /**
     * @var Onepage
     */
    protected $_typeOnepage;

    public function __construct(
        Onepage $typeOnepage,
        \Unirgy\Dropship\Observer\Context $context,
        array $data = []
    )
    {
        $this->_typeOnepage = $typeOnepage;
        parent::__construct($context, $data);
    }

    public function execute(Observer $observer)
    {
        $action = $observer->getControllerAction();
        $req = $action->getRequest();
        $actionName = $req->getFullActionName();
        if ($actionName == 'checkout_onepage_progress') {
            $this->setIsCartUpdateActionFlag(true);
        } elseif (in_array($actionName, array('checkout_onepage_saveBilling','checkout_onepage_saveShipping'))) {
            $usingCase = 1;
            $data = $req->getPost('billing', array());
            if ($actionName == 'checkout_onepage_saveBilling') {
                $usingCase = isset($data['use_for_shipping']) ? (int)$data['use_for_shipping'] : 0;
            }
            $this->setIsCartUpdateActionFlag(true);
            $quote = $this->_typeOnepage->getQuote();
            if ($usingCase) {
                $quote->setRefreshVendorsFlag(true);
            }
        }
    }
}
