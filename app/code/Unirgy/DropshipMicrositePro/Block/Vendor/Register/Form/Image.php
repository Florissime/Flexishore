<?php

namespace Unirgy\DropshipMicrositePro\Block\Vendor\Register\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Image as ElementImage;

class Image extends ElementImage
{
    public function getElementHtml()
    {
        $html = '';

        if ((string)$this->getValue()) {
            $url = $this->_getUrl();

            if( !preg_match("/^http\:\/\/|https\:\/\//", $url) ) {
                $url = $this->_urlBuilder->getBaseUrl('media') . $url;
            }

            $html = '<a href="' . $url . '"'
                . ' onclick="imagePreview(\'' . $this->getHtmlId() . '_image\'); return false;">'
                . '<img src="' . $url . '" id="' . $this->getHtmlId() . '_image" title="' . $this->getValue() . '"'
                . ' alt="' . $this->getValue() . '" height="22" width="22" class="small-image-preview v-middle" />'
                . '</a> ';
        }
        $this->addClass('input-file');
        $this->removeClass('input-text');
        $html .= AbstractElement::getElementHtml();
        $html .= $this->_getDeleteCheckbox();

        return $html;
    }
}