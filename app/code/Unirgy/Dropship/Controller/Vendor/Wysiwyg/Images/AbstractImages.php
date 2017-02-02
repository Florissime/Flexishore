<?php

namespace Unirgy\Dropship\Controller\Vendor\Wysiwyg\Images;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\Dropship\Controller\VendorAbstract;
use Unirgy\Dropship\Helper\Wysiwyg\Images;
use Unirgy\Dropship\Model\Wysiwyg\Images\Storage;

abstract class AbstractImages extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var Storage
     */
    protected $_imagesStorage;

    /**
     * @var Images
     */
    protected $_wysiwygImages;
    protected $catalogHelper;

    public function __construct(
        Context $context,
        ScopeConfigInterface $configScopeConfigInterface, 
        StoreManagerInterface $modelStoreManagerInterface, 
        Registry $frameworkRegistry, 
        Storage $imagesStorage,
        Images $wysiwygImages,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Catalog\Helper\Data $catalogHelper
    )
    {
        $this->catalogHelper = $catalogHelper;
        $this->_resultRawFactory = $resultRawFactory;
        $this->_hlp = $udropshipHelper;
        $this->_registry = $frameworkRegistry;
        $this->_imagesStorage = $imagesStorage;
        $this->_wysiwygImages = $wysiwygImages;

        parent::__construct($context);
    }

    protected function _initAction()
    {
        $this->_setTheme();
        $this->getStorage();
        return $this;
    }

    public function getStorage()
    {
        if (!$this->_registry->registry('storage')) {
            $storage = $this->_imagesStorage;
            $this->_registry->register('storage', $storage);
        }
        return $this->_registry->registry('storage');
    }

    protected function _saveSessionCurrentPath()
    {
        $this->getStorage()
            ->getSession()
            ->setCurrentPath($this->_wysiwygImages->getCurrentPath());
        return $this;
    }

}
