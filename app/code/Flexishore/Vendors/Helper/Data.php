<?php
namespace Flexishore\Vendors\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_csvProcessor;
    protected $_storeManager;
    protected $_objManager;
    protected $_unHelper;
    protected $_scopeConfig;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objManager,
        \Magento\Framework\File\Csv $csvProcessor,
        \Unirgy\Dropship\Helper\Data $unHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_objManager = $objManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_csvProcessor = $csvProcessor;
        $this->_unHelper = $unHelper;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;

        parent::__construct($context);
    }

    public function correctImage($img, $defaulImage)
    {
        $imageVendor = $img;
        $_tmpArr = explode('/', $imageVendor);
        $fileName = '/' . array_pop($_tmpArr);
        $vendorImageDir = '/' . array_pop($_tmpArr);
        $pathImageVendor = BP . '/pub/media/vendor' . $vendorImageDir . $fileName;
        if (!file_exists($pathImageVendor)) {
            $imageVendor = $defaulImage;
        }
        return $imageVendor;
    }

    public function getImageVendor($currentVendor){
        $_vendorData = $this->_unHelper->unserialize($currentVendor->getData('custom_vars_combined'));
        $imageVendor = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        if (!empty($_vendorData['logo'])){
            $imageVendor.= $_vendorData['logo'];
        }
        return $imageVendor;
    }

}