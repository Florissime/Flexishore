<?php

namespace Unirgy\DropshipTierCommission\Block\Adminhtml\SystemConfigField;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipTierCommission\Helper\Data as HelperData;

class Rates extends Field
{
    /**
     * @var HelperData
     */
    protected $_helper;

    protected $_element = null;

    public function __construct(
        Context $context,
        HelperData $helper,
        array $data = []
    ) {
        $this->_helper = $helper;

        parent::__construct($context, $data);
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipTierCommission::udtiercom/system/form_field/category_rates_config.phtml');
        }
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        $html = $this->_toHtml();
        return $html;
    }

    public function getTopCategories()
    {
        return $this->_helper->getTopCategories();
    }

    public function getStore()
    {
        return $this->_storeManager->getDefaultStoreView();
    }

    public function getHelper()
    {
        return $this->_helper;
    }
}
