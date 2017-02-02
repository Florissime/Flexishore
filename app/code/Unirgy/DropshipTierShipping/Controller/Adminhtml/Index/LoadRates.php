<?php

namespace Unirgy\DropshipTierShipping\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Data\Form;
use Magento\Framework\View\Layout;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

class LoadRates extends AbstractIndex
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
        $ctCost = $this->getRequest()->getParam('ct_cost');
        $ctAdditional = $this->getRequest()->getParam('ct_additional');
        $ctHandling = $this->getRequest()->getParam('ct_handling');
        $handlingApply = $this->getRequest()->getParam('handling_apply');
        $calculationMethod = $this->getRequest()->getParam('calculation_method');
        $useSimple = $this->getRequest()->getParam('use_simple');
        if (!$this->_tsHlp->isV2Rates($useSimple) || !$deliveryType) {
            return $this->_response->setBody('');
        }
        /** @var \Magento\Framework\Data\FormFactory $formFactory */
        $formFactory = $this->_hlp->getObj('\Magento\Framework\Data\FormFactory');
        $_form = $formFactory->create();
        if ($this->_tsHlp->isV2SimpleRates($useSimple)) {
            $tplSkuEl = $_form->addField('carriers_udtiership_v2_simple_rates', 'select', [
                'name'=>'groups[udtiership][fields][v2_simple_rates][value]',
                'label'=>__('V2 Simple First/Additional Rates'),
                'value'=>$tsHlp->getV2SimpleRates($deliveryType)
            ]);
            $renderer = $this->_view->getLayout()->createBlock('\Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField\V2\SimpleRates');
        } elseif ($this->_tsHlp->isV2SimpleConditionalRates($useSimple)) {
            $tplSkuEl = $_form->addField('carriers_udtiership_v2_simple_cond_rates', 'select', [
                'name'=>'groups[udtiership][fields][v2_simple_cond_rates][value]',
                'label'=>__('V2 Simple Conditional Rates'),
                'value'=>$tsHlp->getV2SimpleCondRates($deliveryType)
            ]);
            $renderer = $this->_view->getLayout()->createBlock('\Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField\V2\SimpleCondRates');
        } else {
            $tplSkuEl = $_form->addField('carriers_udtiership_v2_rates', 'select', [
                'name'=>'groups[udtiership][fields][v2_rates][value]',
                'label'=>__('V2 Rates'),
                'value'=>$tsHlp->getV2Rates($deliveryType)
            ]);
            $renderer = $this->_view->getLayout()->createBlock('\Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField\V2\Rates');
        }
        $renderer
            ->setDeliveryType($deliveryType)
            ->setCtCost($ctCost)
            ->setCtAdditional($ctAdditional)
            ->setCtHandling($ctHandling)
            ->setHandlingApply($handlingApply)
            ->setCalculationMethod($calculationMethod)
        ;
        return $this->_response->setBody($renderer->getElementHtml($tplSkuEl));
    }
}
