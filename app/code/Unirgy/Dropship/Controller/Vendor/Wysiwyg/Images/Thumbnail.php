<?php

namespace Unirgy\Dropship\Controller\Vendor\Wysiwyg\Images;

use Magento\Framework\Image\Adapter;
use Magento\Store\Model\StoreManagerInterface;

class Thumbnail extends AbstractImages
{
    public function execute()
    {
        $file = $this->getRequest()->getParam('file');
        $file = $this->_wysiwygImages->idDecode($file);
        $thumb = $this->getStorage()->resizeOnTheFly($file);
        if ($thumb !== false) {
            $image = Adapter::factory('GD2');
            $image->open($thumb);
            $image->display();
        } else {
            // todo: genearte some placeholder
        }
    }
}
