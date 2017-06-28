<?php

namespace Flexishore\Search\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_scopeConfig;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeInterface,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    )
    {
        parent::__construct($context);
        $this->_scopeConfig = $scopeInterface;
        $this->_storeManager = $storeManager;
        $this->_objectManager = $objectManager;

    }

    public function getImageFromConfig()
    {
        $fileName = $this->_scopeConfig->getValue(
            'search_by_zip/base/image_for_search',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($fileName){
            return '<img src="'.$this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA ).'flexishore/search/'.$fileName.'" class="extend-img">';
        }
        return false;
    }
}

?>