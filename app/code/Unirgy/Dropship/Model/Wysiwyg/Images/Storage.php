<?php

namespace Unirgy\Dropship\Model\Wysiwyg\Images;

use \Magento\Backend\Model\Session;
use \Magento\Backend\Model\UrlInterface;
use \Magento\Cms\Helper\Wysiwyg\Images;
use \Magento\Cms\Model\Wysiwyg\Images\Storage as ImagesStorage;
use \Magento\Cms\Model\Wysiwyg\Images\Storage\CollectionFactory;
use \Magento\Framework\Filesystem;
use \Magento\Framework\Image\AdapterFactory;
use \Magento\Framework\View\Asset\Repository;
use \Magento\MediaStorage\Helper\File\Storage\Database;
use \Magento\MediaStorage\Model\File\Storage\DatabaseFactory;
use \Magento\MediaStorage\Model\File\Storage\Directory\DatabaseFactory as DirectoryDatabaseFactory;
use \Magento\MediaStorage\Model\File\Storage\FileFactory;
use \Magento\MediaStorage\Model\File\UploaderFactory;
use \Unirgy\Dropship\Helper\Wysiwyg\Images as WysiwygImages;
use \Unirgy\Dropship\Model\Session as ModelSession;

class Storage extends ImagesStorage
{
    /**
     * @var WysiwygImages
     */
    protected $_wysiwygImages;

    /**
     * @var ModelSession
     */
    protected $_modelSession;

    public function __construct(Session $session, 
        UrlInterface $backendUrl, 
        Images $cmsWysiwygImages, 
        Database $coreFileStorageDb, 
        Filesystem $filesystem, 
        AdapterFactory $imageFactory, 
        Repository $assetRepo, 
        CollectionFactory $storageCollectionFactory, 
        FileFactory $storageFileFactory, 
        DatabaseFactory $storageDatabaseFactory, 
        DirectoryDatabaseFactory $directoryDatabaseFactory, 
        UploaderFactory $uploaderFactory, 
        array $resizeParameters = [], 
        array $extensions = [], 
        array $dirs = [], 
        array $data = [], 
        WysiwygImages $wysiwygImages = null, 
        ModelSession $modelSession = null)
    {
        $this->_wysiwygImages = $wysiwygImages;
        $this->_modelSession = $modelSession;

        parent::__construct($session, $backendUrl, $cmsWysiwygImages, $coreFileStorageDb, $filesystem, $imageFactory, $assetRepo, $storageCollectionFactory, $storageFileFactory, $storageDatabaseFactory, $directoryDatabaseFactory, $uploaderFactory, $resizeParameters, $extensions, $dirs, $data);
    }

    public function getHelper()
    {
        return $this->_wysiwygImages;
    }
    public function getSession()
    {
        return $this->_modelSession;
    }
}