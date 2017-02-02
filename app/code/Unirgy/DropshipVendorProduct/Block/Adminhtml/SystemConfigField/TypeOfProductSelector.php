<?php

namespace Unirgy\DropshipVendorProduct\Block\Adminhtml\SystemConfigField;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Layout;

class TypeOfProductSelector extends Field
{
    /**
     * @var Layout
     */
    protected $_viewLayout;

    public function __construct(Context $context, 
        Layout $viewLayout, 
        array $data = [])
    {
        $this->_viewLayout = $viewLayout;

        parent::__construct($context, $data);
        if (($head = $this->_viewLayout->getBlock('head'))) {
            $head->setCanLoadExtJs(true);
        }
    }
    protected function _getElementHtml(AbstractElement $element)
    {
        $elHtml = $element->getElementHtml();
        $htmlId = $element->getHtmlId();
        $targetHtmlId = str_replace('type_of_product', 'value', $htmlId).'_container';
        $targetUrl = $this->getUrl('udprod/udprod/loadTemplateSku', ['_query'=>['type_of_product'=>'TYPEOFPRODUCT']]);
        $elHtml .= "
            <script type=\"text/javascript\">
require(['jquery', 'prototype'], function(jQuery) {

                Event.observe('$htmlId', 'change', function(){
                    if (\$F('$htmlId')) {
                        new Ajax.Updater('$targetHtmlId', '$targetUrl'.replace('TYPEOFPRODUCT', encodeURIComponent(\$F('$htmlId'))), {asynchronous:true, evalScripts:true});
                    }
                });
            
});
</script>";
        return $elHtml;
    }
}