<?php

namespace Unirgy\Dropship\Block\Vendor\Wysiwyg\Images\Content;

use \Magento\Backend\Block\Template;
use \Magento\Backend\Block\Template\Context;
use \Magento\Framework\DataObject;
use \Unirgy\Dropship\Helper\Wysiwyg\Images;
use \Unirgy\Dropship\Model\Wysiwyg\Images\Storage;

class Files extends Template
{
    /**
     * @var Storage
     */
    protected $_imagesStorage;

    /**
     * @var Images
     */
    protected $_wysiwygImages;

    public function __construct(Context $context, 
        array $data = [], 
        Storage $imagesStorage = null, 
        Images $wysiwygImages = null)
    {
        $this->_imagesStorage = $imagesStorage;
        $this->_wysiwygImages = $wysiwygImages;

        parent::__construct($context, $data);
    }

    /**
     * Files collection object
     *
     * @var \Magento\Framework\Data\Collection\Filesystem
     */
    protected $_filesCollection;

    /**
     * Prepared Files collection for current directory
     *
     * @return \Magento\Framework\Data\Collection\Filesystem
     */
    public function getFiles()
    {
        if (! $this->_filesCollection) {
            $this->_filesCollection = $this->_imagesStorage->getFilesCollection($this->_wysiwygImages->getCurrentPath(), $this->_getMediaType());

        }

        return $this->_filesCollection;
    }

    /**
     * Files collection count getter
     *
     * @return int
     */
    public function getFilesCount()
    {
        return $this->getFiles()->count();
    }

    /**
     * File idetifier getter
     *
     * @param  DataObject $file
     * @return string
     */
    public function getFileId(DataObject $file)
    {
        return $file->getId();
    }

    /**
     * File thumb URL getter
     *
     * @param  DataObject $file
     * @return string
     */
    public function getFileThumbUrl(DataObject $file)
    {
        return $file->getThumbUrl();
    }

    /**
     * File name URL getter
     *
     * @param  DataObject $file
     * @return string
     */
    public function getFileName(DataObject $file)
    {
        return $file->getName();
    }

    /**
     * Image file width getter
     *
     * @param  DataObject $file
     * @return string
     */
    public function getFileWidth(DataObject $file)
    {
        return $file->getWidth();
    }

    /**
     * Image file height getter
     *
     * @param  DataObject $file
     * @return string
     */
    public function getFileHeight(DataObject $file)
    {
        return $file->getHeight();
    }

    /**
     * File short name getter
     *
     * @param  DataObject $file
     * @return string
     */
    public function getFileShortName(DataObject $file)
    {
        return $file->getShortName();
    }

    public function getImagesWidth()
    {
        return $this->_imagesStorage->getConfigData('resize_width');
    }

    public function getImagesHeight()
    {
        return $this->_imagesStorage->getConfigData('resize_height');
    }

    /**
     * Return current media type based on request or data
     * @return string
     */
    protected function _getMediaType()
    {
        if ($this->hasData('media_type')) {
            return $this->_getData('media_type');
        }
        return $this->getRequest()->getParam('type');
    }
}
