<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product;

use Magento\Catalog\Helper\Data as CatalogHelperData;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Textarea;
use Magento\Framework\Escaper;
use Magento\Framework\View\Layout;
use Unirgy\Dropship\Helper\Data as HelperData;

class Wysiwyg extends Textarea
{
    /**
     * @var Layout
     */
    protected $_modelLayout;

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var CatalogHelperData
     */
    protected $_catalogHelperData;

    /**
     * @var Config
     */
    protected $_wysiwygConfig;

    public function __construct(Factory $factoryElement, 
        CollectionFactory $factoryCollection, 
        Escaper $escaper, 
        Layout $modelLayout, 
        HelperData $helperData, 
        CatalogHelperData $catalogHelperData, 
        Config $wysiwygConfig)
    {
        $this->_modelLayout = $modelLayout;
        $this->_helperData = $helperData;
        $this->_catalogHelperData = $catalogHelperData;
        $this->_wysiwygConfig = $wysiwygConfig;

        parent::__construct($factoryElement, $factoryCollection, $escaper);
    }

    public function getAfterElementHtml()
    {
        $html = parent::getAfterElementHtml();
        if ($this->isWysiwygAllowed()) {
            $html .= '<br />'
            . $this->_modelLayout
                ->createBlock('adminhtml/widget_button', '', [
                    'label'   => __('WYSIWYG Editor'),
                    'type'    => 'button',
                    'disabled' => false,
                    'class' => 'form-button',
                        'onclick' => 'uVendorWysiwygEditor.open(\''.$this->_helperData->getVendorPortalJsUrl('*/*/wysiwyg').'\', \''.$this->getHtmlId().'\')'
                ])->toHtml();
        }
        return $html;
    }

    public function isWysiwygAllowed()
    {
        if ($this->_helperData->isWysiwygAllowed()
            && $this->_catalogHelperData->isModuleEnabled('Magento_Cms')
            && (bool)$this->_wysiwygConfig->isEnabled()
            && $this->getProductAttribute()
        ) {
            return $this->getProductAttribute()->getIsWysiwygEnabled();
        }
        return false;
    }
}