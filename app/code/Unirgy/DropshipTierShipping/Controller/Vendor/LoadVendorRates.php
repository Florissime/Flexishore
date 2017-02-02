<?php

namespace Unirgy\DropshipTierShipping\Controller\Vendor;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\View\Layout;
use Magento\Store\Model\StoreManagerInterface;

class LoadVendorRates extends AbstractVendor
{
    public function execute()
    {
        $session = $this->_hlp->session();
        $tsHlp = $this->_hlp->getObj('\Unirgy\DropshipTierShipping\Helper\Data');
        $deliveryType = $this->getRequest()->getParam('delivery_type');
        $vId = $session->getVendorId();
        /** @var Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        if (!$tsHlp->isV2Rates() || !$deliveryType) {
            return $result->setContents("");
        }
        /** @var \Magento\Framework\Data\FormFactory $_form */
        $formFactory = $this->_hlp->getObj('\Magento\Framework\Data\FormFactory');
        $_form = $formFactory->create();
        $extraCond = [
            '__use_vendor' => true,
        ];
        if (!empty($vId)) {
            $extraCond['vendor_id=?'] = $vId;
        } else {
            $extraCond[] = new \Zend_Db_Expr('false');
        }
        $layout = $this->_view->getLayout();
        if ($tsHlp->isV2SimpleRates()) {
            $ratesEl = $_form->addField('tiership_v2_simple_rates', 'select', [
                'name' => 'tiership_v2_simple_rates',
                'label' => __('V2 Simple First/Additional Rates'),
                'value' => $tsHlp->getV2SimpleRates($deliveryType, $extraCond)
            ]);
            /** @var \Unirgy\DropshipTierShipping\Block\Vendor\V2\SimpleRates $renderer */
            $renderer = $layout->createBlock('Unirgy\DropshipTierShipping\Block\Vendor\V2\SimpleRates');
        } elseif ($tsHlp->isV2SimpleConditionalRates()) {
            $ratesEl = $_form->addField('tiership_v2_simple_cond_rates', 'select', [
                'name' => 'tiership_v2_simple_cond_rates',
                'label' => __('V2 Simple Conditional Rates'),
                'value' => $tsHlp->getV2SimpleCondRates($deliveryType, $extraCond)
            ]);
            /** @var \Unirgy\DropshipTierShipping\Block\Vendor\V2\SimpleCondRates $renderer */
            $renderer = $layout->createBlock('Unirgy\DropshipTierShipping\Block\Vendor\V2\SimpleCondRates');
        } else {
            $ratesEl = $_form->addField('tiership_v2_rates', 'select', [
                'name' => 'tiership_v2_rates',
                'label' => __('V2 Rates'),
                'value' => $tsHlp->getV2Rates($deliveryType, $extraCond)
            ]);
            /** @var \Unirgy\DropshipTierShipping\Block\Vendor\V2\Rates $renderer */
            $renderer = $layout->createBlock('Unirgy\DropshipTierShipping\Block\Vendor\V2\Rates');
        }
        $ratesEl->setDeliveryType($deliveryType);
        $renderer->setDeliveryType($deliveryType);
        return $result->setContents($renderer->render($ratesEl));
    }
}
