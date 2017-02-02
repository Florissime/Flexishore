<?php

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

/**
 * Class Rates
 * @package Unirgy\DropshipTierShipping\Block\Adminhtml\SystemConfigField
 */
class Rates extends Field
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var null
     */
    protected $_element = null;

    /**
     * Rates constructor.
     * @param Context $context
     * @param HelperData $helperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        HelperData $helperData,
        array $data = []
    ) {
        $this->_helperData = $helperData;

        parent::__construct($context, $data);
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipTierShipping::udtiership/system/form_field/category_rates_config.phtml');
        }
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        $html = $this->_toHtml();
        return $html;
    }

    /**
     * @return \Magento\Framework\Data\Collection
     */
    public function getTopCategories()
    {
        return $this->_helperData->getTopCategories();
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface|null
     */
    public function getStore()
    {
        return $this->_storeManager->getDefaultStoreView();
    }

    /**
     * @param $subkeyColumns
     * @param $idx
     * @return string
     */
    public function getColumnTitle($subkeyColumns, $idx)
    {
        reset($subkeyColumns);
        $i = 0;
        while ($i++ != $idx) next($subkeyColumns);
        $title = '';
        switch (current($subkeyColumns)) {
            case 'cost':
                $title = __('Cost for the first item');
                break;
            case 'additional':
                $title = __('Additional item cost');
                break;
            case 'handling':
                $title = __('Tier handling fee');
                break;
        }
        return $title;
    }

    /**
     * @return bool
     */
    public function isShowAdditionalColumn()
    {
        return $this->_helperData->useAdditional($this->getStore());
    }

    /**
     * @return bool
     */
    public function isShowHandlingColumn()
    {
        return $this->_helperData->useHandling($this->getStore());
    }

}
