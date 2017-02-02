<?php

namespace Unirgy\DropshipTierCommission\Block\Adminhtml\VendorEditTab\ComRates\Renderer;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipTierCommission\Helper\Data as HelperData;

//use Magento\Framework\Model\App;

class Rates extends Widget implements RendererInterface
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

        $this->setTemplate('Unirgy_DropshipTierCommission::udtiercom/vendor/helper/category_rates_config.phtml');
    }

    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->toHtml();
    }

    public function setElement(AbstractElement $element)
    {
        $this->_element = $element;
        return $this;
    }

    public function getElement()
    {
        return $this->_element;
    }

    public function getTopCategories()
    {
        return $this->_helper->getTopCategories();
    }

    public function getTiercomRates()
    {
        $value = $this->_element->getValue();
        if (is_string($value)) {
            $value = unserialize($value);
        }
        if (!is_array($value)) {
            $value = [];
        }
        return $value;
    }

    public function getGlobalTierComConfig()
    {
        $value = $this->_scopeConfig->getValue('udropship/tiercom/rates', ScopeInterface::SCOPE_STORE,
                                               $this->getStore());
        if (is_string($value)) {
            $value = unserialize($value);
        }
        return $value;
    }

    public function getStore()
    {
        if ($this->getElement()->getStore()) {
            return $this->getElement()->getStore();
        }
        return 0; // App::ADMIN_STORE_ID; // todo find alternative!!!
    }
}
