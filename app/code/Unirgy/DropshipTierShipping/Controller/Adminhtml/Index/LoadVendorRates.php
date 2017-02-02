<?php

namespace Unirgy\DropshipTierShipping\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Data\Form;
use Magento\Framework\View\Layout;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

class LoadVendorRates extends AbstractIndex
{
    /**
     * @var HelperData
     */
    protected $_tsHlp;
    protected $_hlp;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        HelperData $tiershipHelper,
        Context $context
    )
    {
        $this->_tsHlp = $tiershipHelper;
        $this->_hlp = $udropshipHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        $tsHlp = $this->_tsHlp;
        $deliveryType = $this->getRequest()->getParam('delivery_type');
        $vId = $this->getRequest()->getParam('vendor_id');
        if (!$this->_tsHlp->isV2Rates() || !$deliveryType) {
            return $this->_response->setBody('');
        }
        /** @var \Magento\Framework\Data\FormFactory $formFactory */
        $formFactory = $this->_hlp->getObj('\Magento\Framework\Data\FormFactory');
        $_form = $formFactory->create();
        $extraCond = [
            '__use_vendor'=>true,
        ];
        if (!empty($vId)) {
            $extraCond['vendor_id=?'] = $vId;
        } else {
            $extraCond[] = new \Zend_Db_Expr('false');
        }
        if ($this->_tsHlp->isV2SimpleRates()) {
            $ratesEl = $_form->addField('tiership_v2_simple_rates', 'select', [
                'name'=>'tiership_v2_simple_rates',
                'label'=>__('V2 Simple First/Additional Rates'),
                'value'=>$tsHlp->getV2SimpleRates($deliveryType, $extraCond)
            ]);
            $renderer = $this->_view->getLayout()->createBlock('\Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Renderer\SimpleRates');
        } elseif ($this->_tsHlp->isV2SimpleConditionalRates()) {
            $ratesEl = $_form->addField('tiership_v2_simple_cond_rates', 'select', [
                'name'=>'tiership_v2_simple_cond_rates',
                'label'=>__('V2 Simple Conditional Rates'),
                'value'=>$tsHlp->getV2SimpleCondRates($deliveryType, $extraCond)
            ]);
            $renderer = $this->_view->getLayout()->createBlock('\Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Renderer\SimpleCondRates');
        } else {
            $ratesEl = $_form->addField('tiership_v2_rates', 'select', [
                'name'=>'tiership_v2_rates',
                'label'=>__('V2 Rates'),
                'value'=>$tsHlp->getV2Rates($deliveryType, $extraCond)
            ]);
            $renderer = $this->_view->getLayout()->createBlock('\Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Renderer\Rates');
        }
        $ratesEl->setDeliveryType($deliveryType);
        $renderer->setDeliveryType($deliveryType);
        return $this->_response->setBody($renderer->render($ratesEl));
    }
}
