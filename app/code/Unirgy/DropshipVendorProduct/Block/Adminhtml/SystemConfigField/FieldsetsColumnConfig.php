<?php

namespace Unirgy\DropshipVendorProduct\Block\Adminhtml\SystemConfigField;

use Unirgy\DropshipVendorProduct\Helper\Data as DropshipVendorProductHelperData;
use Unirgy\Dropship\Block\Adminhtml\SystemConfigFormField\FieldContainer;
use Unirgy\Dropship\Helper\Data as HelperData;

class FieldsetsColumnConfig extends FieldContainer
{
    /**
     * @var DropshipVendorProductHelperData
     */
    protected $_prodHlp;

    public function __construct(
        DropshipVendorProductHelperData $helperData,
        HelperData $helper,
        \Magento\Backend\Block\Template\Context $context,
        array $data = [])
    {
        $this->_prodHlp = $helperData;

        parent::__construct($helper, $context, $data);
    }

    public function getEditFieldsConfig()
    {
        return $this->_prodHlp->getEditFieldsConfig();
    }
}