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

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Statement;

use \Magento\Backend\Block\Widget\Context;
use \Magento\Backend\Block\Widget\Form\Container;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Model\Vendor\Statement;

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

    public function __construct(
        Registry $registry,
        HelperData $helperData,
        Context $context,
        array $data = []
    )
    {
        $this->_registry = $registry;
        $this->_hlp = $helperData;

        parent::__construct($context, $data);

        $this->setData('form_action_url', $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))));
    }

    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Unirgy_Dropship';
        $this->_controller = 'adminhtml_vendor_statement';

        parent::_construct();

        $this->updateButton('delete', 'label', __('Delete Statement'));
        /** @var \Unirgy\Dropship\Model\Vendor\Statement $model */
        $model = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor\Statement')
            ->load($this->getRequest()->getParam($this->_objectId));
        $this->_registry->register('statement_data', $model);
        if ($this->_hlp->isUdpayoutActive()) {
            $this->addButton('save_pay', array(
                'id'      => 'statement_save_pay_btn',
                'label'   => __('Save and Pay'),
                'class'   => 'save',
            ), 1);
        }
        $this->addButton('save_refresh', array(
            'id'      => 'statement_save_refresh_btn',
            'label'   => __('Save and Refresh'),
            'class'   => 'save',
        ), 1);
    }

    public function getHeaderText()
    {
        return __('Statement');
    }
    public function getFormScripts()
    {
    ob_start();
?>
<script type="text/javascript">
require(["jquery","prototype","domReady!"], function(jQuery) {
    <?php if ($this->_hlp->isUdpayoutActive()): ?>
    $('statement_save_pay_btn').observe('click', function () {
        $('pay_flag').value=1;
        $('edit_form').submit();
    });
    <?php endif ?>
    $('statement_save_refresh_btn').observe('click', function () {
        $('refresh_flag').value=1;
        $('edit_form').submit();
    });
});
</script>
        <?php
        return ob_get_clean();
    }
}
