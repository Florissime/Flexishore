<?php

namespace Unirgy\Dropship\Controller\Vendor;

class Preferences extends AbstractVendor
{
    public function execute()
    {
        return $this->_renderPage(array('uwysiwyg_editor', 'uwysiwyg_editor_js'), 'preferences');
    }
}
