<?php

namespace Unirgy\DropshipTierShipping\Block\ProductAttribute\Form;

use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Select;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;

/**
 * Class UseCustom
 * @package Unirgy\DropshipTierShipping\Block\ProductAttribute\Form
 */
class UseCustom extends Select
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * UseCustom constructor.
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param Registry $frameworkRegistry
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        Registry $frameworkRegistry,
        $data = []
    ) {
        $this->_coreRegistry = $frameworkRegistry;

        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    /**
     * @return mixed|string
     */
    public function getAfterElementHtml()
    {
        $html = parent::getAfterElementHtml();
        $htmlId = $this->getHtmlId();
        $ratesHtmlId = str_replace('udtiership_use_custom', 'udtiership_rates', $htmlId);
        $curProd = $this->_coreRegistry->registry('current_product');
        if ($curProd && $curProd->getData('_edit_in_vendor')) {
            $trTag = 'li';
        } else {
            $trTag = 'div.field';
        }
        $html .= <<<JS
<script type="text/javascript">
require(["jquery", "prototype","domReady!"], function(jQuery) {
var syncUdtiershipUseCustom = function() {
    if (\$('$ratesHtmlId') && (trElem = \$('$ratesHtmlId').up("$trTag"))) {
        if (\$F('$htmlId') && \$F('$htmlId')!='0') {
            trElem.show();
            trElem.select('select').invoke('enable');
            trElem.select('input').invoke('enable');
            trElem.select('textarea').invoke('enable');
        } else {
            trElem.hide();
            trElem.select('select').invoke('disable');
      trElem.select('input').invoke('disable');
            trElem.select('textarea').invoke('disable');
        }
    }
}
$('$htmlId').observe('change', syncUdtiershipUseCustom);
syncUdtiershipUseCustom();
});
</script>
JS;
        return $html;
    }
}
