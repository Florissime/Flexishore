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
use \Zend_Json;

class DependSelect extends Field
{
	protected $_dependsConfig;
	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		array $data = [],
		array $dependsConfig = []
	)
	{
		$this->_dependsConfig = $dependsConfig;
		return parent::__construct($context, $data);
	}
    public function render(AbstractElement $element)
    {
        $html = parent::render($element);
		$elId = $element->getId();
        if (isset($this->_dependsConfig[$elId]) && ($dependFields = (array)$this->_dependsConfig[$elId])) {
            foreach ($dependFields as &$dv) {
                $dv = explode(',', $dv);
            }
            $dfJson = \Zend_Json::encode($dependFields);
            $html .=<<<EOT
<script type="text/javascript">
require(["jquery","prototype","domReady!"], function(jQuery) {
	var df = \$H($dfJson)
	$('{$element->getHtmlId()}')['syncDependFields'] = function() {
	    df.each(function(pair){
			if ((trElem = $("row_"+pair.key)) || $(pair.key) && (trElem = $(pair.key).up("tr"))) {
				if (\$A(pair.value).indexOf($('{$element->getHtmlId()}').value) != -1) {
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
            	if ($(pair.key) && $(pair.key)['syncDependFields']) {
            	    $(pair.key)['syncDependFields'].defer();
                }
			}
		})
	}
    $('{$element->getHtmlId()}').observe('change', $('{$element->getHtmlId()}')['syncDependFields']);
    $('{$element->getHtmlId()}')['syncDependFields'].defer();
})
</script>
EOT;
        }
        return $html;
    }
}