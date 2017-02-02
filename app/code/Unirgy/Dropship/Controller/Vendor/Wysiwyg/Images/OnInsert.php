<?php

namespace Unirgy\Dropship\Controller\Vendor\Wysiwyg\Images;

use Magento\Store\Model\StoreManagerInterface;

class OnInsert extends AbstractImages
{
    public function execute()
    {
        $helper = $this->_wysiwygImages;
        $storeId = $this->getRequest()->getParam('store');

        $filename = $this->getRequest()->getParam('filename');
        $filename = $helper->idDecode($filename);
        $asIs = $this->getRequest()->getParam('as_is');

        $this->catalogHelper->setStoreId($storeId);
        $helper->setStoreId($storeId);

        $image = $helper->getImageHtmlDeclaration($filename, $asIs);
        $this->_resultRawFactory->create()->setContents($image);
    }
}
