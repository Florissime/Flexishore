<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField\V2;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class DeliveryTypeSelector
 * @package Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField\V2
 */
class DeliveryTypeSelector extends Field
{
    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $elHtml = $element->getElementHtml();
        $htmlId = $element->getHtmlId();
        $useSimpleHtmlId = str_replace('delivery_type_selector', 'use_simple_rates', $htmlId);
        $ctCostHtmlId = str_replace('delivery_type_selector', 'cost_calculation_type', $htmlId);
        $ctAdditionalHtmlId = str_replace('delivery_type_selector', 'additional_calculation_type', $htmlId);
        $ctHandlingHtmlId = str_replace('delivery_type_selector', 'handling_calculation_type', $htmlId);
        $handlingApplyHtmlId = str_replace('delivery_type_selector', 'handling_apply_method', $htmlId);
        $calculationMethodHtmlId = str_replace('delivery_type_selector', 'calculation_method', $htmlId);
        $simpleTargetHtmlId = str_replace('delivery_type_selector', 'v2_simple_rates', $htmlId).'_container';
        $simpleCondTargetHtmlId = str_replace('delivery_type_selector', 'v2_simple_cond_rates', $htmlId).'_container';
        $targetHtmlId = str_replace('delivery_type_selector', 'v2_rates', $htmlId).'_container';
        $targetUrl = $this->getUrl('udtiership/index/loadRates', ['delivery_type'=>'DELIVERYTYPE','use_simple'=>'USESIMPLE','ct_cost'=>'CTCOST','ct_additional'=>'CTADDITIONAL','ct_handling'=>'CTHANDLING','handling_apply'=>'HANDLINGAPPLY','calculation_method'=>'CALCULATIONMETHOD']);
        $elHtml .= <<<JS
<script type="text/javascript">
require(["jquery", "prototype"], function(jQuery) {
    Event.observe('$htmlId', 'change', function(){
        if (\$F('$htmlId')) {
            var _simpleTargetHtmlId = '$simpleTargetHtmlId';
            var _simpleCondTargetHtmlId = '$simpleCondTargetHtmlId';
            var _targetHtmlId = '$targetHtmlId';
            var targetHtmlId, otherTargetHtmlId;
            if (\$F('$useSimpleHtmlId') == 3) {
                targetHtmlId = _simpleTargetHtmlId;
                otherTargetHtmlId = [$(_targetHtmlId), $(_simpleCondTargetHtmlId)];
            } else if (\$F('$useSimpleHtmlId') == 4) {
                otherTargetHtmlId = [$(_simpleTargetHtmlId), $(_targetHtmlId)];
                targetHtmlId = _simpleCondTargetHtmlId;
            } else {
                otherTargetHtmlId = [$(_simpleTargetHtmlId), $(_simpleCondTargetHtmlId)];
                targetHtmlId = _targetHtmlId;
            }
            \$A(otherTargetHtmlId).invoke('update', '');
            new Ajax.Updater(targetHtmlId, '$targetUrl'.replace('DELIVERYTYPE', \$F('$htmlId')).replace('USESIMPLE', \$F('$useSimpleHtmlId')).replace('CTCOST', \$F('$ctCostHtmlId')).replace('CTADDITIONAL', \$F('$ctAdditionalHtmlId')).replace('CTHANDLING', \$F('$ctHandlingHtmlId')).replace('HANDLINGAPPLY', \$F('$handlingApplyHtmlId')).replace('CALCULATIONMETHOD', \$F('$calculationMethodHtmlId')), {asynchronous:true, evalScripts:true});
        }
    });
});
</script>
JS;
        return $elHtml;
    }
}
