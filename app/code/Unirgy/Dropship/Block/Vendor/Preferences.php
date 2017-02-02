<?php

namespace Unirgy\Dropship\Block\Vendor;

use \Magento\Directory\Model\Config\Source\Country;
use \Magento\Framework\Registry;
use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Model\Source;

class Preferences extends Template
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var Country
     */
    protected $_sourceCountry;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var Source
     */
    protected $_src;

    /**
     * @var \Unirgy\Dropship\Model\Config
     */
    protected $udropshipConfig;

    public function __construct(
        \Unirgy\Dropship\Model\Config $udropshipConfig,
        HelperData $helper,
        Country $sourceCountry,
        Registry $registry,
        Source $source,
        Context $context,
        array $data = []
    )
    {
        $this->_hlp = $helper;
        $this->udropshipConfig = $udropshipConfig;
        $this->_sourceCountry = $sourceCountry;
        $this->_registry = $registry;
        $this->_src = $source;

        parent::__construct($context, $data);
    }

    public function getFieldsets()
    {
        $hlp = $this->_hlp;

        $visible = $this->_scopeConfig->getValue('udropship/vendor/visible_preferences');
        $visible = $visible ? explode(',', $visible) : false;

        $fieldsets = array();
        foreach ($this->udropshipConfig->getFieldset() as $code=>$node) {
            if (@$node['modules'] && !$hlp->isModulesActive((string)$node['modules'])
                || @$node['hide_modules'] && $hlp->isModulesActive((string)@$node['hide_modules'])
                || @$node['hidden']
            ) {
                continue;
            }
            $fieldsets[$code] = array(
                'position' => (int)$node['position'],
                'legend' => (string)$node['legend'],
            );
        }
        foreach ($this->udropshipConfig->getField() as $code=>$node) {
            if (empty($fieldsets[(string)@$node['fieldset']]) || @$node['disabled']) {
                continue;
            }
            if (@$node['modules'] && !$hlp->isModulesActive((string)$node['modules'])
                || @$node['hide_modules'] && $hlp->isModulesActive((string)@$node['hide_modules'])
            ) {
                continue;
            }
            if ($visible && !in_array($code, $visible)) {
                continue;
            }
            $type = @$node['type'] ? (string)@$node['type'] : 'text';

            $field = array(
                'position' => (int)@$node['position'],
                'type' => $type,
                'name' => @$node['name'] ? (string)@$node['name'] : $code,
                'label' => (string)@$node['label'],
                'class' => (string)@$node['class'],
                'note' => (string)@$node['note'],
            );
            switch ($type) {
            case 'statement_po_type': case 'payout_po_status_type': case 'notify_lowstock':
            case 'select': case 'multiselect': case 'checkboxes': case 'radios':
                $source = $this->_hlp->getObj(@$node['source_model'] ? (string)@$node['source_model'] : '\Unirgy\Dropship\Model\Source');
                if (is_callable(array($source, 'setPath'))) {
                    $source->setPath(@$node['source'] ? (string)$node['source'] : $code);
                }
                $field['options'] = $source->toOptionArray();
                if (@$node['depend_fields']) {
                    $field['depend_select'] = 1;
                    $field['field_config'] = array(
                        'depend_fields' => $node['depend_fields']
                    );
                }
                break;
            }
            $fieldsets[(string)@$node['fieldset']]['fields'][$code] = $field;
        }

        $fieldsets['account'] = array(
            'position' => 0,
            'legend' => 'Account Information',
            'fields' => array(
                'vendor_name' => array(
                    'position' => 1,
                    'name' => 'vendor_name',
                    'type' => 'text',
                    'label' => 'Vendor Name',
                ),
                'vendor_attn' => array(
                    'position' => 2,
                    'name' => 'vendor_attn',
                    'type' => 'text',
                    'label' => 'Attention To',
                ),
                'email' => array(
                    'position' => 3,
                    'name' => 'email',
                    'type' => 'text',
                    'label' => 'Email Address / Login',
                ),
                'password' => array(
                    'position' => 4,
                    'name' => 'password',
                    'type' => 'text',
                    'label' => 'Login Password',
                ),
                'telephone' => array(
                    'position' => 5,
                    'name' => 'telephone',
                    'type' => 'text',
                    'label' => 'Telephone',
                ),
            ),
        );

        $countries = $this->_sourceCountry->toOptionArray();

        $countryId = null;
        $region = null;
        if ($this->_registry->registry('vendor_data')) {
            $countryId = $this->_registry->registry('vendor_data')->getCountryId();
            $region = $this->_registry->registry('vendor_data')->getRegionCode();
            $this->_registry->registry('vendor_data')->setRegion($region);
            $bRegion = $this->_registry->registry('vendor_data')->getBillingRegionCode();
            $this->_registry->registry('vendor_data')->setBillingRegion($bRegion);
        } elseif ($_v = $this->_hlp->session()->getVendor()) {
            $countryId = $_v->getCountryId();
            $region = $_v->getRegionCode();
            $_v->setRegion($region);
            $bRegion = $_v->getBillingRegionCode();
            $_v->setBillingRegion($bRegion);
        }
        if (!$countryId) {
            $countryId = $this->_scopeConfig->getValue('general/country/default');
        }

        $regionCollection = $this->_hlp->createObj('\Magento\Directory\Model\Region')
            ->getCollection()
            ->addCountryFilter($countryId);

        $regions = $regionCollection->toOptionArray();

        if ($regions) {
            $regions[0]['label'] = __('Please select state...');
        } else {
            $regions = array(array('value'=>'', 'label'=>''));
        }

        $fieldsets['shipping_origin'] = array(
            'position' => 1,
            'legend' => 'Shipping Origin Address',
            'fields' => array(
                'street' => array(
                    'position' => 1,
                    'name' => 'street',
                    'type' => 'textarea',
                    'label' => 'Street',
                ),
                'city' => array(
                    'position' => 2,
                    'name' => 'city',
                    'type' => 'text',
                    'label' => 'City',
                ),
                'zip' => array(
                    'position' => 3,
                    'name' => 'zip',
                    'type' => 'text',
                    'label' => 'Zip / Postal code',
                ),
                'country_id' => array(
                    'position' => 4,
                    'name' => 'country_id',
                    'type' => 'select',
                    'label' => 'Country',
                    'options' => $countries,
                ),
                'region_id' => array(
                    'position' => 5,
                    'name' => 'region_id',
                    'type' => 'select',
                    'label' => 'State',
                    //'options' => $regions,
                ),
                'region' => array(
                    'position' => 6,
                    'name' => 'region',
                    'type' => 'text',
                    'label' => '',
                ),
            ),
        );

        $fieldsets['billing_address'] = array(
            'position' => 2,
            'legend' => 'Billing Address',
            'fields' => array(
                'billing_use_shipping' => array(
                    'position' => -1,
                    'name' => 'billing_use_shipping',
                    'type' => 'select',
                    'label' => 'Same as Shipping',
                    'options' => $this->_src->setPath('billing_use_shipping')->toOptionArray(),
                    'depend_select' => 1,
                    'field_config' => array(
                        'depend_fields' => array(
                            'billing_vendor_attn' => '0',
                            'billing_street' => '0',
                            'billing_city' => '0',
                            'billing_zip' => '0',
                            'billing_country_id' => '0',
                            'billing_region_id' => '0',
                            'billing_region' => '0',
                            'billing_email' => '0',
                            'billing_telephone' => '0',
                            'billing_fax' => '0',
                        )
                    )
                ),
                'billing_vendor_attn' => array(
                    'position' => 0,
                    'name' => 'billing_vendor_attn',
                    'type' => 'text',
                    'label' => 'Attention To',
                    'note'  => 'Leave empty to use default'
                ),
                'billing_street' => array(
                    'position' => 1,
                    'name' => 'billing_street',
                    'type' => 'textarea',
                    'label' => 'Street',
                ),
                'billing_city' => array(
                    'position' => 2,
                    'name' => 'billing_city',
                    'type' => 'text',
                    'label' => 'City',
                ),
                'billing_zip' => array(
                    'position' => 3,
                    'name' => 'billing_zip',
                    'type' => 'text',
                    'label' => 'Zip / Postal code',
                ),
                'billing_country_id' => array(
                    'position' => 4,
                    'name' => 'billing_country_id',
                    'type' => 'select',
                    'label' => 'Country',
                    'options' => $countries,
                ),
                'billing_region_id' => array(
                    'position' => 5,
                    'name' => 'billing_region_id',
                    'type' => 'select',
                    'label' => 'State',
                    //'options' => $regions,
                ),
                'billing_region' => array(
                    'position' => 6,
                    'name' => 'billing_region',
                    'type' => 'text',
                    'label' => '',
                ),
                'billing_email' => array(
                    'position' => 7,
                    'name' => 'billing_email',
                    'type' => 'text',
                    'label' => 'Email',
                    'note'  => 'Leave empty to use default'
                ),
                'billing_telephone' => array(
                    'position' => 8,
                    'name' => 'billing_telephone',
                    'type' => 'text',
                    'label' => 'Telephone',
                    'note'  => 'Leave empty to use default'
                ),
                'billing_fax' => array(
                    'position' => 9,
                    'name' => 'billing_fax',
                    'type' => 'text',
                    'label' => 'Fax',
                    'note'  => 'Leave empty to use default'
                ),
            ),
        );

        $this->_eventManager->dispatch('udropship_vendor_front_preferences', array(
            'fieldsets'=>&$fieldsets
        ));

        uasort($fieldsets, array($hlp, 'usortByPosition'));
        foreach ($fieldsets as $k=>$v) {
            if (empty($v['fields'])) {
                continue;
            }
            uasort($v['fields'], array($hlp, 'usortByPosition'));
        }

        return $fieldsets;
    }

    public function getDependSelectJs($htmlId, $field)
    {
        $html = '';
        $fc = (array)@$field['field_config'];
        if (isset($fc['depend_fields']) && ($dependFields = (array)$fc['depend_fields'])
            || isset($fc['hide_depend_fields']) && ($hideDependFields = (array)$fc['hide_depend_fields'])
        ) {
            if (!empty($dependFields)) {
                foreach ($dependFields as &$dv) {
                    $dv = $dv!='' ? explode(',', $dv) : array('');
                }
                unset($dv);
                $dfJson = \Zend_Json::encode($dependFields);
            } else {
                $dfJson = '{}';
            }
            if (!empty($hideDependFields)) {
                foreach ($hideDependFields as &$dv) {
                    $dv = $dv!='' ? explode(',', $dv) : array('');
                }
                unset($dv);
                $hideDfJson = \Zend_Json::encode($hideDependFields);
            } else {
                $hideDfJson = '{}';
            }
            $html .=<<<EOT
<script type="text/javascript">
require(["jquery","prototype","domReady!"], function(jQuery) {
	var df = \$H($dfJson);
	var hideDf = \$H($hideDfJson);
	var enableDisable = function (pair, flag) {
        if ($(pair.key) && (trElem = $(pair.key).up("tr,.form-group"))) {
            if (flag == (\$A(pair.value).indexOf($('{$htmlId}').value) != -1)) {
                trElem.show()
                trElem.select('select').invoke('enable')
                trElem.select('input').invoke('enable')
                trElem.select('textarea').invoke('enable')
            } else {
                trElem.hide()
                trElem.select('select').invoke('disable')
                trElem.select('input').invoke('disable')
                trElem.select('textarea').invoke('disable')
            }
        }
    }
	var syncDependFields = function() {
		df.each(function(pair){
			enableDisable(pair, true);
		});
		hideDf.each(function(pair){
			enableDisable(pair, false);
		});
	}
    $('{$htmlId}').observe('change', syncDependFields)
    syncDependFields()
})
</script>
EOT;
        }
        return $html;
    }

    public function getStatementPoTypeJs()
    {
        $defPoType = (string)$this->_scopeConfig->getValue('udropship/statement/statement_po_type');
        $html = '
<script type="text/javascript">
require(["jquery","prototype","domReady!"], function(jQuery) {
var switchStatementPoStatusSelect = function() {
	for (i=0; i<$("statement_po_type").options.length; i++) {
	    var poTypeValue = $("statement_po_type").value;
        if (poTypeValue == "999") {
            poTypeValue = "' . $defPoType . '";
        }
		var statusSel = $("statement_"+$("statement_po_type").options[i].value+"_status")
		if (statusSel) {
    		if (statusSel.id == "statement_"+poTypeValue+"_status") {
    			statusSel.up("tr,.form-group").show()
    			statusSel.enable()
    		} else {
    			statusSel.up("tr,.form-group").hide()
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

    public function getPayoutPoStatusTypeJs()
    {
        $html = '
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
    			statusSel.up("tr,.form-group").show();
    			statusSel.enable();
    		} else {
    			statusSel.up("tr,.form-group").hide();
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

    public function getNotifyLowstockJs()
    {
        $html = parent::getAfterElementHtml();
        $html .= '
<script type="text/javascript">
require(["jquery","prototype","domReady!"], function(jQuery) {
var switchNotifyLowstockSelect = function() {
	if ($("notify_lowstock").value==1) {
		$("notify_lowstock_qty").up("tr,.form-group").show()
		$("notify_lowstock_qty").enable()
	} else {
		$("notify_lowstock_qty").up("tr,.form-group").hide()
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