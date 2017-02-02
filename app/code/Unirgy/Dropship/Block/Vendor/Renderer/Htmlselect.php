<?php

namespace Unirgy\Dropship\Block\Vendor\Renderer;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Data\Form\Element\CollectionFactory;
use \Magento\Framework\Data\Form\Element\Factory;
use \Magento\Framework\Data\Form\Element\Select;
use \Magento\Framework\Escaper;
use \Magento\Framework\Url;
use \Unirgy\Dropship\Model\Source;

class Htmlselect extends \Magento\Backend\Block\Widget
{
    protected $_coreRegistry;

    protected $_collectionFactory;

    protected $_resourceHelper;

    protected $_hlp;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Unirgy\Dropship\Model\ResourceModel\Vendor\CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_hlp = $udropshipHelper;
        $this->_resourceHelper = $resourceHelper;
        $this->_collectionFactory = $collectionFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Define block template
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setTemplate('Unirgy_Dropship::vendor/htmlselect.phtml');
        parent::_construct();
    }

    public function getVendorName()
    {
        $v = $this->_hlp->getVendor($this->getElement()->getValue());
        return $v->getId() ? $v->getVendorName() : __('* Select Vendor');
    }

    public function getDataGroup()
    {
        return 'general';
    }

    /**
     * @return array
     */
    public function getSelectorOptions()
    {
        return [
            'source' => $this->getUrl('udropship/index/vendorAutocomplete'),
            'minLength' => 0,
            'ajaxOptions' => ['data' => ['template_id' => 1]],
            'template' => '[data-template-for="'.$this->getElement()->getHtmlId().'-' . $this->getDataGroup() . '"]',
            'data' => $this->getSuggestedVendors('')
        ];
    }


    public function getSuggestedVendors($labelPart)
    {
        $escapedLabelPart = $this->_resourceHelper->addLikeEscape(
            $labelPart,
            ['position' => 'any']
        );
        $collection = $this->_collectionFactory->create()->addFieldToFilter(
            'vendor_name',
            ['like' => $escapedLabelPart]
        );

        $collection->setPageSize(20);

        $result = [];
        foreach ($collection->getItems() as $vendor) {
            $result[] = [
                'id' => $vendor->getId(),
                'label' => $vendor->getVendorName(),
                'code' => $vendor->getId(),
            ];
        }
        return $result;
    }
}