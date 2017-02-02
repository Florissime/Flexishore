<?php

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\GridRenderer;

use \Magento\Backend\Block\Context;
use \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number;
use \Magento\Framework\DataObject;
use \Unirgy\Dropship\Helper\Data as HelperData;

class SpecialPrice extends Number
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
        $hlp = $this->_hlp;
        $fromDate = $row->getData('special_from_date');
        $toDate = $row->getData('special_to_date');
        if ($fromDate) {
            $fromDate = $hlp->dateInternalToLocale($fromDate);
        }
        if ($toDate) {
            $toDate = $hlp->dateInternalToLocale($toDate);
        }
        $spLabel = __('Special Price');
        $sfdLabel = __('From Date');
        $stdLabel = __('To Date');
        $_dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);

        $dataInit = 'data-mage-init="' . $this->escapeHtml(
            json_encode(
                [
                    'calendar' => [
                        'dateFormat' => $_dateFormat,
                        'showsTime' => false,
                        'buttonText' => 'Select Date',
                    ],
                ]
            )
        ) . '"';

        if ($this->getColumn()->getEditable()) {
        $htmlId = '_'.md5(uniqid(microtime(), true));
        $html .=<<<EOT
<nobr><br />
$sfdLabel <input id="{$htmlId}_sfd" type="text" class="input-text" name="_special_from_date" value="$fromDate" style="width:110px !important;" $dataInit />
</nobr><br />
<nobr>
$stdLabel <input id="{$htmlId}_std" type="text" class="input-text" name="_special_to_date" value="$toDate" style="width:110px !important;" $dataInit />
</nobr>
EOT;
        }
        return $html;
    }
}