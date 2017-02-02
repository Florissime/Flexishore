<?php

namespace Unirgy\Dropship\Model;

use \Magento\Catalog\Model\Product\Image;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Framework\Filesystem\Io\File;
use \Magento\Framework\Image\Factory;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Registry;
use \Magento\Framework\View\Asset\Repository;
use \Magento\Framework\View\DesignInterface;
use \Magento\Framework\View\FileSystem;
use \Magento\MediaStorage\Helper\File\Storage\Database;
use \Magento\Store\Model\StoreManagerInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;

class ProductImage extends Image
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(
        HelperData $helperData,
        Context $context,
        Registry $registry, 
        StoreManagerInterface $storeManager, 
        \Magento\Catalog\Model\Product\Media\Config $catalogProductMediaConfig,
        Database $coreFileStorageDatabase,
        \Magento\Framework\Filesystem $filesystem,
        Factory $imageFactory, 
        Repository $assetRepo, 
        FileSystem $viewFileSystem, 
        ScopeConfigInterface $scopeConfig,
        DirectoryList $dirList,
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        $this->_dirList = $dirList;

        parent::__construct($context, $registry, $storeManager, $catalogProductMediaConfig, $coreFileStorageDatabase, $filesystem, $imageFactory, $assetRepo, $viewFileSystem, $scopeConfig, $resource, $resourceCollection, $data);
    }

    public function setBaseFile($file)
    {
        $this->_isBaseFilePlaceholder = false;

        if ($file && 0 !== strpos($file, '/', 0)) {
            $file = '/' . $file;
        }
        $baseDir = '';

        if ('/no_selection' == $file) {
            $file = null;
        }
        if ($file) {
            if (!$this->_fileExists($baseDir . $file) || !$this->_checkMemory($baseDir . $file)) {
                $file = null;
            }
        }
        if (!$file) {
            $this->_isBaseFilePlaceholder = true;
            // check if placeholder defined in config
            $isConfigPlaceholder = $this->_scopeConfig->getValue(
                "catalog/placeholder/{$this->getDestinationSubdir()}_placeholder",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $configPlaceholder = '/placeholder/' . $isConfigPlaceholder;
            if (!empty($isConfigPlaceholder) && $this->_fileExists($baseDir . $configPlaceholder)) {
                $file = $configPlaceholder;
            } else {
                $this->_newFile = true;
                return $this;
            }
        }

        $baseFile = $baseDir . $file;

        if (!$file || !$this->_mediaDirectory->isFile($baseFile)) {
            throw new \Exception(__('We can\'t find the image file.'));
        }

        $this->_baseFile = $baseFile;

        // build new filename (most important params)
        $path = [
            $this->_catalogProductMediaConfig->getBaseMediaPath(),
            'cache',
            $this->_storeManager->getStore()->getId(),
            $path[] = $this->getDestinationSubdir(),
        ];
        if (!empty($this->_width) || !empty($this->_height)) {
            $path[] = "{$this->_width}x{$this->_height}";
        }

        // add misk params as a hash
        $miscParams = [
            ($this->_keepAspectRatio ? '' : 'non') . 'proportional',
            ($this->_keepFrame ? '' : 'no') . 'frame',
            ($this->_keepTransparency ? '' : 'no') . 'transparency',
            ($this->_constrainOnly ? 'do' : 'not') . 'constrainonly',
            $this->_rgbToString($this->_backgroundColor),
            'angle' . $this->_angle,
            'quality' . $this->_quality,
        ];

        // if has watermark add watermark params to hash
        if ($this->getWatermarkFile()) {
            $miscParams[] = $this->getWatermarkFile();
            $miscParams[] = $this->getWatermarkImageOpacity();
            $miscParams[] = $this->getWatermarkPosition();
            $miscParams[] = $this->getWatermarkWidth();
            $miscParams[] = $this->getWatermarkHeight();
        }

        $path[] = md5(implode('_', $miscParams));

        // append prepared filename
        $this->_newFile = implode('/', $path) . $file;
        // the $file contains heading slash

        return $this;
    }

    public function clearCache($vId=null)
    {
        $hasImageUpload = true;
        $subDir = '';
        if ($vId instanceof Vendor) {
            $hasImageUpload = $vId->hasImageUpload();
            $vId = $vId->getId();
            $subDir = '/vendor/'.$vId;
        }
        if (!$hasImageUpload) return;
        $directory = $this->_dirList->getPath('media').'/cache'.$subDir.'/';
        $io = new File();
        $io->rmdir($directory, true);

        $this->_coreFileStorageDatabase->deleteFolder($directory);
    }
}