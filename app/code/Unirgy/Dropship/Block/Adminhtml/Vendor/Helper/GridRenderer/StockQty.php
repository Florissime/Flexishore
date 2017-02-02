<?php

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\GridRenderer;

use \Magento\Backend\Block\Context;
use \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number;
use \Magento\Framework\DataObject;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Model\Source;

class StockQty extends Number
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(
        HelperData $helperData,
        Context $context,
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        parent::__construct($context, $data);
    }

    public function render(DataObject $row)
    {
        $html = parent::render($row);
        return $html;
    }
}