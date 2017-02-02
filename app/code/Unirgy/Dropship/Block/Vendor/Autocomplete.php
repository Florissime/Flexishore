<?php

namespace Unirgy\Dropship\Block\Vendor;

use \Magento\Framework\Db\Select;
use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Unirgy\Dropship\Model\Vendor;

class Autocomplete extends Template
{
    /**
     * @var Vendor
     */
    protected $_modelVendor;

    public function __construct(Context $context, 
        array $data = [], 
        Vendor $modelVendor = null)
    {
        $this->_modelVendor = $modelVendor;

        parent::__construct($context, $data);
    }

    protected $_vendorPrefix;
    
    public function setVendorPrefix($vendorPrefix)
    {
        $this->_vendorPrefix = $vendorPrefix;
        return $this;
    }
    public function getVendorPrefix()
    {
        return $this->_vendorPrefix;
    }
    
    public function getSuggestData()
    {
        $vendors = $this->_modelVendor->getCollection()->setOrder('vendor_name', 'asc')->setPageSize(20);
        $vendors->getSelect()
            ->reset(Select::COLUMNS)
            ->columns(array('vendor_name', 'vendor_id'))
            ->where('vendor_name like ?', $this->getVendorPrefix().'%');
        return $vendors;
    }
    
    protected function _toHtml()
    {
        $html = '<ul><li style="display:none"></li>';
        $sd = $this->getSuggestData();
        foreach ($sd as $index => $item) {
            $rowClass = $index%2?'odd':'even';
            if ($index == 0) {
                $rowClass .= ' first';
            }

            if ($index == $sd->count()) {
                $rowClass .= ' last';
            }

            $html .=  '<li style="margin: 0px; min-height: 1.3em" title="'.$item->getId().'" class="'.$rowClass.'">'
                .$this->escapeHtml($item->getVendorName())
                .($index == $sd->count() && $sd->getSize()>$sd->count() ? '<span>...</span></li>' : '</li>');
        }

        $html.= '</ul>';

        return $html;
    }
}