<?php

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\GridRenderer;

use \Magento\Backend\Block\Context;
use \Magento\Backend\Block\Widget\Grid\Column\Renderer\Date as RendererDate;
use \Magento\Framework\DataObject;
use \Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Date extends RendererDate
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(
        HelperData $helperData,
        Context $context,
        DateTimeFormatterInterface $dateTimeFormatter, 
        array $data = []
    )
    {
        $this->_hlp = $helperData;

        parent::__construct($context, $dateTimeFormatter, $data);
    }

    public function render(DataObject $row)
    {
        $html = parent::render($row);
        $hlp = $this->_hlp;
        if ($this->getColumn()->getEditable()) {
            $date = $row->getData($this->getColumn()->getIndex());
            if ($date) {
                $date = $hlp->dateInternalToLocale($date);
            }
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

            $htmlId = '_'.md5(uniqid(microtime(), true));
            $html .=<<<EOT
<input id="$htmlId" type="text" class="input-text" name="{$this->getColumn()->getId()}" value="$date" style="width:110px !important;" $dataInit />
EOT;

        }
        return $html;
    }
}