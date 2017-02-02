<?php

namespace Unirgy\Dropship\Block\Adminhtml\Batch;

use \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use \Magento\Framework\DataObject;

class Action extends AbstractRenderer
{
    public function render(DataObject $row)
    {
        $href = $this->getUrl('udropship/batch/batchLabels', array('batch_id'=>$row->getId()));
        return '<a href="'.$href.'">'.__('Download Labels').'</a>';
    }
}