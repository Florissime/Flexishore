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

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Edit\Tab;

use \Magento\Framework\Registry;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Custom extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
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
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->_registry = $registry;
        $this->_hlp = $helperData;

        parent::__construct($context, $registry, $formFactory, $data);
        $this->setDestElementId('vendor_custom');
        //$this->setTemplate('udropship/vendor/form.phtml');
    }

    protected function _prepareForm()
    {
        $vendor = $this->_registry->registry('vendor_data');
        $hlp = $this->_hlp;
        $id = $this->getRequest()->getParam('id');
        $form = $this->_formFactory->create();
        $this->setForm($form);

        $fieldset = $form->addFieldset('custom', array(
            'legend'=>__('Custom Vendor Information')
        ));

        $fieldset->addField('custom_data_combined', 'textarea', array(
            'name'      => 'custom_data_combined',
            'label'     => __('Custom Data'),
            'style'     => 'height:500px',
            'note'      => __("
Enter custom data for this vendor.<br/>
Each part should start with:<br/>
<pre>===== part_name =====</pre><br/>
Parts can be referenced from product template like this:
<xmp>
<?php echo \Magento\Framework\App\ObjectManager::getInstance()->get('\Unirgy\Dropship\Helper\Data')
  ->getVendor(\$_product)
    ->getData('part_name')?>
</xmp>
"
            ),
        ));

        if ($vendor) {
            $form->setValues($vendor->getData());
        }

        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('Custom Data');
    }
    public function getTabTitle()
    {
        return __('Custom Data');
    }
    public function canShowTab()
    {
        return true;
    }
    public function isHidden()
    {
        return false;
    }

}
