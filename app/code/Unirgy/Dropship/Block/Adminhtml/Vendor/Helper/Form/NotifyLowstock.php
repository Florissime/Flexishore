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

use \Magento\Framework\Data\Form\Element\Select;

class NotifyLowstock extends Select
{
    public function getAfterElementHtml()
    {
        $html = parent::getAfterElementHtml();
        $html .= '
<script type="text/javascript">
require(["jquery","prototype","domReady!"], function(jQuery) {
var switchNotifyLowstockSelect = function() {
	if ($("notify_lowstock").value==1) {
		$("notify_lowstock_qty").up("div.field").show()
		$("notify_lowstock_qty").enable()
	} else {
		$("notify_lowstock_qty").up("div.field").hide()
		$("notify_lowstock_qty").disable()
	}
}
$("notify_lowstock").observe("change", switchNotifyLowstockSelect);
switchNotifyLowstockSelect();
});
</script>        	
        ';
        return $html;
    }
}

