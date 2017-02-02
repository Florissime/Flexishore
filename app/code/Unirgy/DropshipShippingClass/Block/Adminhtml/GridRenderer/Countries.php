<?php

namespace Unirgy\DropshipShippingClass\Block\Adminhtml\GridRenderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\Locale\TranslatedLists;

class Countries extends AbstractRenderer
{
    protected $_translated;

    public function __construct(Context $context, TranslatedLists $translatedLists, array $data = [])
    {
        $this->_translated = $translatedLists;
        parent::__construct($context, $data);
    }

    public function render(DataObject $row)
    {
        if (($rows = $row->getRows()) && is_array($rows)) {
            $countryNames = [];
            foreach ($rows as $row) {
                $_name = $this->_translated->getCountryTranslation($row['country_id']);
                $countryNames[] = $_name ? $_name : $row['country_id'];
            }
            $countryNames = implode(', ', $countryNames);
            if (empty($countryNames)) {
                $countryNames = $this->escapeHtml($countryNames);
            }
            return $countryNames;
        }
        return null;
    }
}
