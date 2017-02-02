<?php

namespace Unirgy\Dropship\Controller\Vendor\Wysiwyg\Images;

use Magento\Framework\Controller\Result\RawFactory;
use Magento\Store\Model\StoreManagerInterface;

class TreeJson extends AbstractImages
{
    public function execute()
    {
        try {
            $this->_initAction();
            /** @var \Unirgy\Dropship\Block\Vendor\Wysiwyg\Images\Tree $treeBlock */
            $treeBlock = $this->_view->getLayout()->createBlock('\Unirgy\Dropship\Block\Vendor\Wysiwyg\Images\Tree');
            return $this->_resultRawFactory->create()->setContents(
                $treeBlock->getTreeJson()
            );
        } catch (\Exception $e) {
            return $this->_resultRawFactory->create()->setContents($this->_hlp->jsonEncode([]));
        }
    }
}
