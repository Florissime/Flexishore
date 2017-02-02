<?php

namespace Unirgy\DropshipShippingClass\Block\Adminhtml\GridRenderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Postcodes extends AbstractRenderer
{
    public function render(DataObject $row)
    {
        if (($rows = $row->getRows()) && is_array($rows)) {
            $postCodes = [];
            foreach ($rows as $row) {
                $postCodes[] = $row['country_id'].': '.($row['postcode'] ? $row['postcode'] : '*');
            }
            return implode("<br />", $postCodes);
        }
        return null;
    }
}