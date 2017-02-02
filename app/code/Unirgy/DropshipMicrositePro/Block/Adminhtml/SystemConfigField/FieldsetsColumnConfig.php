<?php

namespace Unirgy\DropshipMicrositePro\Block\Adminhtml\SystemConfigField;

use Magento\Backend\Block\Context;
use Unirgy\DropshipMicrositePro\Helper\Data as HelperData;
use Unirgy\Dropship\Block\Adminhtml\SystemConfigFormField\FieldContainer;

class FieldsetsColumnConfig extends FieldContainer
{
    /**
     * @var HelperData
     */
    protected $_mspHlp;

    public function __construct(
        HelperData $helperData,
        \Magento\Backend\Block\Template\Context $context,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        array $data = []
    )
    {
        $this->_mspHlp = $helperData;

        parent::__construct($udropshipHelper, $context, $data);
    }

    public function getEditFieldsConfig()
    {
        return $this->_mspHlp->getRegistrationFieldsConfig();
    }
}
