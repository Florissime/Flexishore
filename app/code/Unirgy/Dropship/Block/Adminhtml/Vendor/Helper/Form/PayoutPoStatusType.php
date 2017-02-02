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

class PayoutPoStatusType extends Select
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
        $html .= '
<script type="text/javascript">
require(["jquery","prototype","domReady!"], function(jQuery) {
var switchPayoutPoStatusSelect = function() {
    var defStPoType = "'.($this->_scopeConfig->getValue('udropship/statement/statement_po_type')).'";
    var getStPoType = function(val) {
        return val == "999" ? defStPoType : val;
    }
    for (i=0; i<$("statement_po_type").options.length; i++) {
		var statusSel = $("payout_"+getStPoType($("statement_po_type").options[i].value)+"_status");
		if (statusSel) {
    		if (statusSel.id == "payout_"+getStPoType($("statement_po_type").value)+"_status" && $("payout_po_status_type").value == "payout") {
    			statusSel.up("div.field").show();
    			statusSel.enable();
    		} else {
    			statusSel.up("div.field").hide();
    			statusSel.disable();
    		}
		}
	}
}
$("payout_po_status_type").observe("change", switchPayoutPoStatusSelect)
$("statement_po_type").observe("change", switchPayoutPoStatusSelect)
switchPayoutPoStatusSelect();
});
</script>        	
        ';
        return $html;
    }
}

