<?php

namespace Unirgy\Dropship\Block;

use \Magento\Catalog\Model\Category;

class Categories extends \Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Category
{
    protected $_url;
    public function __construct(
        \Magento\Framework\Url $url,
        \Magento\Framework\Data\Form\Element\Factory $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        \Magento\Framework\Escaper $escaper,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $collectionFactory,
        \Magento\Backend\Helper\Data $backendData,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\AuthorizationInterface $authorization,
        array $data = []
    ) {
        $this->_url = $url;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $collectionFactory, $backendData, $layout, $jsonEncoder, $authorization, $data);
    }
    public function getAfterElementHtml()
    {
        $htmlId = $this->getHtmlId();
        $suggestPlaceholder = __('start typing to search category');
        $selectorOptions = $this->_jsonEncoder->encode($this->_getSelectorOptions());

        $return = <<<HTML
    <input id="{$htmlId}-suggest" placeholder="$suggestPlaceholder" />
    <script>
        require(["jquery", "mage/mage"], function($){
            $('#{$htmlId}-suggest').mage('treeSuggest', {$selectorOptions});
        });
    </script>
HTML;
        return $return;
    }
    protected function _getSelectorOptions()
    {
        return [
            'source' => $this->_url->getUrl('udropship/index/suggestCategories'),
            'valueField' => '#' . $this->getHtmlId(),
            'className' => 'category-select',
            'multiselect' => true,
            'showAll' => true
        ];
    }
}