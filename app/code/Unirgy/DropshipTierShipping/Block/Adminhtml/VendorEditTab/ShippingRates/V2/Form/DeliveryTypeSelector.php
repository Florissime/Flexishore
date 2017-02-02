<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Form;

use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Select;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

/**
 * Class DeliveryTypeSelector
 * @package Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Form
 */
class DeliveryTypeSelector extends Select
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var UrlInterface
     */
    protected $_url;

    /**
     * DeliveryTypeSelector constructor.
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param Registry $registry
     * @param HelperData $helperData
     * @param UrlInterface $url
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        Registry $registry,
        HelperData $helperData,
        UrlInterface $url,
        $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_helperData = $helperData;
        $this->_url = $url;

        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    /**
     * @return mixed|string
     */
    public function getAfterElementHtml()
    {
        $vendor = $this->_coreRegistry->registry('vendor_data');
        $vId = $vendor ? $vendor->getId() : 0;
        $html = parent::getAfterElementHtml();
        $htmlId = $this->getHtmlId();

        if ($this->_helperData->isV2SimpleRates()) {
            $targetHtmlId = str_replace('delivery_type_selector', 'v2_simple_rates', $htmlId) . '_container';
        } elseif ($this->_helperData->isV2SimpleConditionalRates()) {
            $targetHtmlId = str_replace('delivery_type_selector', 'v2_simple_cond_rates', $htmlId) . '_container';
        } else {
            $targetHtmlId = str_replace('delivery_type_selector', 'v2_rates', $htmlId) . '_container';
        }

        $targetUrl = $this->_url->getUrl('udtiership/index/loadVendorRates', [
            'delivery_type' => 'DELIVERYTYPE',
            'vendor_id' => $vId
        ]);
        $html .= "
            <script type='text/javascript'>
require(['jquery', 'prototype'], function(jQuery) {

                Event.observe('$htmlId', 'change', function(){
                    if (\$F('$htmlId')) {
                        var targetHtmlId = '$targetHtmlId';
                        new Ajax.Updater(targetHtmlId, '$targetUrl'.replace('DELIVERYTYPE', \$F('$htmlId')), {asynchronous:true, evalScripts:true});
                    }
                });
            
});
</script>";
        return $html;
    }
}
