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
 * @package    \Unirgy\Dropship
 * @copyright  Copyright (c) 2015-2016 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Data\Form\Element\CollectionFactory;
use \Magento\Framework\Data\Form\Element\Factory;
use \Magento\Framework\Data\Form\Element\Select;
use \Magento\Framework\Escaper;

class StatementPoType extends Select
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Factory $factoryElement,
        CollectionFactory $factoryCollection, 
        Escaper $escaper,
        $data = []
    )
    {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    public function getAfterElementHtml()
    {
        $html = parent::getAfterElementHtml();
        $defPoType = (string)$this->_scopeConfig->getValue('udropship/statement/statement_po_type');
        $html .= '
<script type="text/javascript">
require(["jquery","prototype","domReady!"], function(jQuery) {
var switchStatementPoStatusSelect = function() {
	for (i=0; i<$("statement_po_type").options.length; i++) {
	    var poTypeValue = $("statement_po_type").value;
        if (poTypeValue == "999") {
            poTypeValue = "'.$defPoType.'";
        }
		var statusSel = $("statement_"+$("statement_po_type").options[i].value+"_status")
		if (statusSel) {
    		if (statusSel.id == "statement_"+poTypeValue+"_status") {
    			statusSel.up("div.field").show()
    			statusSel.enable()
    		} else {
    			statusSel.up("div.field").hide()
    			statusSel.disable()
    		}
		}
	}
}
$("statement_po_type").observe("change", switchStatementPoStatusSelect);
switchStatementPoStatusSelect();
});
</script>        	
        ';
        return $html;
    }
}

