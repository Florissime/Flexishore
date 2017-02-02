<?php

namespace Unirgy\DropshipShippingClass\Block\Adminhtml\GridRenderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Regions extends AbstractRenderer
{
    public function render(DataObject $row)
    {
        if (($rows = $row->getRows()) && is_array($rows)) {
            $regionCodes = [];
            foreach ($rows as $row) {
                $regionCodes[] = $row['country_id'].': '.($row['region_id'] ? @implode(',', $row['region_codes']) : '*');
            }
            return implode("<br />", $regionCodes);
        }
        return null;
    }
}