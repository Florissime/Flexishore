<?php

namespace Unirgy\Dropship\Model\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{
    protected $_translate = [];
    public function convert($dom)
    {
        $this->_translate = [];
        $output = [];
        foreach ($dom->firstChild->childNodes as $childNode) {
            $output += $this->_convertNode($childNode);
        }
        return ['output'=>$output, 'translate'=>$this->_translate];
    }

    protected function _convertNode(\DOMNode $node, $path = '')
    {
        $output = [];
        if ($node->nodeType == XML_ELEMENT_NODE) {
            if ($node->hasAttributes()) {
                $translate = $node->attributes->getNamedItem('translate');
                if ($translate) {
                    $this->_translate[$path] = explode(' ', $translate->nodeValue);
                }
            }
            $nodeData = [];
            /** @var $childNode \DOMNode */
            foreach ($node->childNodes as $childNode) {
                $childrenData = $this->_convertNode($childNode, ($path ? $path . '/' : '') . $childNode->nodeName);
                if ($childrenData == null) {
                    continue;
                }
                if (is_array($childrenData)) {
                    $nodeData = array_merge($nodeData, $childrenData);
                } else {
                    $nodeData = $childrenData;
                }
            }
            if (is_array($nodeData) && empty($nodeData)) {
                $nodeData = null;
            }
            $output[$node->nodeName] = $nodeData;
        } elseif ($node->nodeType == XML_CDATA_SECTION_NODE || $node->nodeType == XML_TEXT_NODE && trim(
                $node->nodeValue
            ) != ''
        ) {
            return $node->nodeValue;
        }

        return $output;
    }
}