<?php

namespace Unirgy\Dropship\Controller\Vendor;

class Wysiwyg extends AbstractVendor
{
    public function execute()
    {
        $elementId = $this->getRequest()->getParam('element_id', md5(microtime()));
        $storeId = $this->getRequest()->getParam('store_id', 0);
        $storeMediaUrl = $this->_url->getBaseUrl(['_type'=>'media']);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle($resultPage->getDefaultLayoutHandle());

        $resultPage->getLayout()->getBlock('\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\Wysiwyg')->addData(array(
            'editor_element_id' => $elementId,
            'store_id'          => $storeId,
            'store_media_url'   => $storeMediaUrl,
        ));

        $resultPage->renderResult($this->_response);
        return $this->_response;
    }
}
