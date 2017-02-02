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
 * @package    \Unirgy\DropshipPo
 * @copyright  Copyright (c) 2015-2016 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */
 
namespace Unirgy\Dropship\Block\Adminhtml\SystemConfigFormField;

use \Magento\Config\Block\System\Config\Form\Field;
use \Magento\Framework\Data\Form\Element\AbstractElement;

class Depend extends Field
{
    public function render(AbstractElement $element)
    {
        $html = parent::render($element);
        $fc = (array)$element->getData('field_config');
        if (isset($fc['depend_value']) && isset($fc['depend_field']) && ($df = $fc['depend_field'])) {
            if (!is_array($df)) {
                $df = explode(',', $df);
            }
            $dfJson = \Zend_Json::encode($df);
            $dfValueJson = \Zend_Json::encode($fc['depend_value']);
            $html .=<<<EOT
<script type="text/javascript">
require(["jquery","prototype","domReady!"], function(jQuery) {
	var df = \$A($dfJson)
	var dfVal = $dfValueJson
	var syncDependFields = function() {
		for (i=0; i<df.size(); i++) {
			if ($(df[i])) {
				if ($('{$element->getHtmlId()}').value == dfVal) {
					$(df[i]).up("tr").show()
            		$(df[i]).enable()
            	} else {
            		$(df[i]).up("tr").hide()
            		$(df[i]).disable()
            	}
			} 
		}
	}
    $('{$element->getHtmlId()}').observe('change', syncDependFields)
    syncDependFields()
})
</script>
EOT;
        }
        return $html;
    }
}