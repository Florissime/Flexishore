<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipVendorProduct\Controller\Vendor;

use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Registry;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorProduct\Helper\Data as DropshipVendorProductHelperData;
use Unirgy\Dropship\Controller\Vendor\AbstractVendor as VendorAbstractVendor;
use Unirgy\Dropship\Helper\Data as HelperData;

abstract class AbstractVendor extends VendorAbstractVendor
{
    /**
     * @var DropshipVendorProductHelperData
     */
    protected $_prodHlp;

    /**
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    protected $_storageDatabase;
    protected $_helperCatalog;
    protected $_modelProductFactory;

    public function __construct(
        DropshipVendorProductHelperData $helperData,
        Context $context,
        ScopeConfigInterface $scopeConfig, 
        DesignInterface $viewDesignInterface, 
        StoreManagerInterface $storeManager, 
        LayoutFactory $viewLayoutFactory, 
        Registry $registry, 
        ForwardFactory $resultForwardFactory, 
        HelperData $helper, 
        PageFactory $resultPageFactory, 
        RawFactory $resultRawFactory, 
        Header $httpHeader,
        \Unirgy\Dropship\Helper\Catalog $helperCatalog,
        \Unirgy\DropshipVendorProduct\Model\ProductFactory $modelProductFactory,
        \Magento\MediaStorage\Helper\File\Storage\Database $storageDatabase
    )
    {
        $this->_prodHlp = $helperData;
        $this->_helperCatalog = $helperCatalog;
        $this->_modelProductFactory = $modelProductFactory;
        $this->_storageDatabase = $storageDatabase;

        parent::__construct($context, $scopeConfig, $viewDesignInterface, $storeManager, $viewLayoutFactory, $registry, $resultForwardFactory, $helper, $resultPageFactory, $resultRawFactory, $httpHeader);
    }

    protected function _checkProduct($productId=null)
    {
        $this->_prodHlp->checkProduct($productId);
        return $this;
    }

    protected function _redirectUrl($url)
    {
        $this->_response->setRedirect($url);
    }
    
    protected function _redirectAfterPost($prod=null)
    {
        $session = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
        $r = $this->getRequest();
        if (!$r->getParam('continue_edit')) {
            if ($session->getUdprodLastGridUrl()) {
                $this->_redirectUrl($session->getUdprodLastGridUrl());
            } else {
                $this->_redirect('udprod/vendor/products');
            }
        } else {
            if (isset($prod) && $prod->getId()) {
                $this->_redirect('udprod/vendor/productEdit', ['id'=>$prod->getId()]);
            } else {
                $this->_redirect('udprod/vendor/productNew', ['_current'=>true]);
            }
        }
    }
    protected function _initProduct()
    {
        $r = $this->getRequest();
        $v = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendor();
        $productId  = (int) $this->getRequest()->getParam('id');
        $productData = $r->getPost('product');
        $product = $this->_prodHlp->initProductEdit([
            'id'   => $productId,
            'data' => $productData,
            'vendor' => $v
        ]);
        if (isset($productData['options'])) {
            $product->setProductOptions($productData['options']);
        }
        $product->setCanSaveCustomOptions(
            (bool)$this->getRequest()->getPost('affect_product_custom_options')
        );
        return $product;
    }
    /*
    */
    public function returnResult($result)
    {
        $this->_resultRawFactory->create()->setContents($this->_hlp->jsonEncode($result));
    }
}