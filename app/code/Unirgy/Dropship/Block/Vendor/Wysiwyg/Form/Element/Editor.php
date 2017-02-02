<?php

namespace Unirgy\Dropship\Block\Vendor\Wysiwyg\Form\Element;

use \Magento\Framework\Data\Form\Element\Editor as ElementEditor;

class Editor extends ElementEditor
{
	protected function _getPluginButtonsHtml($visible = true)
    {
    	return '';
    }
}