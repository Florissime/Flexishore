<?php

namespace Unirgy\Dropship\Helper\Wysiwyg;

use \Magento\Backend\Helper\Data as HelperData;
use \Magento\Cms\Helper\Wysiwyg\Images as WysiwygImages;
use \Magento\Cms\Model\Wysiwyg\Config;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\Filesystem;
use \Magento\Store\Model\StoreManagerInterface;
use \Unirgy\Dropship\Model\Session;

class Images extends WysiwygImages
{

    /**
     * @var \Unirgy\Dropship\Helper\Data
     */
    protected $_hlp;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_dirList;

    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $dirList,
        \Unirgy\Dropship\Helper\Data $helper,
        Context $context,
        HelperData $backendData, 
        Filesystem $filesystem, 
        StoreManagerInterface $storeManager
    )
    {

        $this->_dirList = $dirList;
        $this->_hlp = $helper;

        parent::__construct($context, $backendData, $filesystem, $storeManager);
    }

    public function getStorageRoot()
    {
        return $this->_dirList->getPath('media') . '/' . Config::IMAGE_DIRECTORY
            . '/' . 'udvendor-'.$this->_hlp->session()->getVendorId();
    }
    public function isUsingStaticUrlsAllowed()
    {
        return true;
    }
}