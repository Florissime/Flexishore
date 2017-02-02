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

namespace Unirgy\Dropship\Block\Adminhtml\Vendor;

use \Magento\Backend\Block\Widget\Context;
use \Magento\Backend\Block\Widget\Form\Container;
use \Magento\Backend\Model\Session;
use \Magento\Directory\Helper\Data as DirectoryHelperData;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Model\Vendor;
use \Zend_Json;

class Edit extends Container
{
    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var DirectoryHelperData
     */
    protected $_directoryHelperData;

    public function __construct(
        Registry $registry,
        HelperData $helperData,
        DirectoryHelperData $directoryHelperData,
        Context $context,
        array $data = []
    )
    {
        $this->_registry = $registry;
        $this->_hlp = $helperData;
        $this->_directoryHelperData = $directoryHelperData;

        parent::__construct($context, $data);

        $this->setData('form_action_url', $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))));
    }

    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Unirgy_Dropship';
        $this->_controller = 'adminhtml_vendor';

        parent::_construct();

        $this->updateButton('save', 'label', __('Save Vendor'));
        $this->updateButton('save_continue', 'label', __('Save and Continue Edit'));
        $this->addButton('save_continue_btn', array(
            'label'     => __('Save and Continue Edit'),
            'class'     => 'save',
        ), 10);
        $this->updateButton('delete', 'label', __('Delete Vendor'));

        if( $this->getRequest()->getParam($this->_objectId) ) {
            $vendor = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor')
                ->load($this->getRequest()->getParam($this->_objectId));
            $this->_registry->register('vendor_data', $vendor);
        } elseif (($sessVD = $this->_hlp->getObj('Magento\Backend\Model\Session')->getData('uvendor_edit_data', true))) {
            unset($sessVD['logo']);
            if ($this->_registry->registry('vendor_data')) {
                $this->_registry->registry('vendor_data')->setData($sessVD);
            } else {
                $this->_registry->register('vendor_data', $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor')->setData($sessVD));
            }
        }
    }

    public function getHeaderText()
    {
        if( $this->_registry->registry('vendor_data') && $this->_registry->registry('vendor_data')->getId() ) {
            return __("Edit Vendor '%1'", $this->escapeHtml($this->_registry->registry('vendor_data')->getVendorName()));
        } else {
            return __('New Vendor');
        }
    }

    public function getFormScripts()
    {
        ob_start();
?>
<script type="text/javascript">

var deps = [];
deps.push('prototype');
deps.push('mage/adminhtml/grid');
deps.push("domReady!");

require(['jquery','mage/backend/form','mage/backend/validation'], function($) {
    $('#edit_form').form().validation('option', 'ignore', ":hidden,:disabled");
    $('#save_continue_btn').click(function () {
        $('save_continue').val(1);
        $('#edit_form').form().submit();
    });
});

require(deps, function() {

    var updater = new RegionUpdater('country_id', 'region', 'region_id', <?php echo $this->_directoryHelperData->getRegionJson() ?>, 'disable');
    var bUpdater = new RegionUpdater('billing_country_id', 'billing_region', 'billing_region_id', <?php echo $this->_directoryHelperData->getRegionJson() ?>, 'disable');

    function udSyncRegField(countrySel) {
        var regionIdRow = countrySel.up(1).next();
        var regionRow = countrySel.up(1).next(1);
        var regionIdSel = regionIdRow.select('select[name=region_id],select[name=billing_region_id]')
        if (regionIdSel && (regionIdSel = regionIdSel[0])) {
            if (regionIdSel.disabled) {
                regionIdRow.hide();//.select('select,input').invoke('disable');
                regionRow.show();//.select('select,input').invoke('enable');
            } else {
                regionIdRow.show();//.select('select,input').invoke('enable');
                regionRow.hide();//.select('select,input').invoke('disable');
            }
        }
    }

    udSyncRegField($('country_id'));
    if (!$F('billing_use_shipping')) {
        udSyncRegField($('billing_country_id'));
    }
    varienGlobalEvents.attachEventHandler("address_country_changed", udSyncRegField);

    if (typeof udropship_vendor_productsJsObject != "undefined") {

    }
});

</script>
<?php
        return ob_get_clean();
    }
}
