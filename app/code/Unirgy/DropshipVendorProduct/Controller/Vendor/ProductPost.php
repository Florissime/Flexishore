<?php

namespace Unirgy\DropshipVendorProduct\Controller\Vendor;

use Magento\Backend\Helper\Js;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Model\App;
use Magento\Framework\Registry;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorProduct\Helper\Data as DropshipVendorProductHelperData;
use Unirgy\Dropship\Helper\Data as HelperData;

class ProductPost extends AbstractVendor
{
    public function execute()
    {
        $session = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
        $v = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendor();
        $hlp = $this->_hlp;
        $prHlp = $this->_prodHlp;
        $r = $this->getRequest();
        $oldStoreId = $this->_storeManager->getStore()->getId();
        $this->_storeManager->setCurrentStore(0);
        if ($r->isPost()) {
            try {
                $prod = $this->_initProduct();
                $isNew = !$prod->getId();
                if (!$this->_hlp->getScopeFlag('udprod/general/disable_name_check')) {
                    $ufName = $prod->formatUrlKey($prod->getName());
                    if (!trim($ufName)) {
                        throw new \Exception(__('Product name is invalid'));
                    }
                }
                $prHlp->checkUniqueVendorSku($prod, $v);
                if ($isNew) {
                    $prod->setUdprodIsNew(true);
                }
                if ($downloadable = $r->getPost('downloadable')) {
                    $prod->setDownloadableData($downloadable);
                }
                if ($links = $this->getRequest()->getPost('links')) {
                    if (isset($links['grouped'])) {
                        $prod->setGroupedLinkData($this->_hlp->getObj('\Magento\Backend\Helper\Js')->decodeGridSerializedInput($links['grouped']));
                    }
                }
                $canSaveCustOpt = $prod->getCanSaveCustomOptions();
                $custOptAll = [];
                if (!$isNew && $canSaveCustOpt) {
                    $__custOptAll = $prod->getOptions();
                    foreach ($__custOptAll as $__custOpt) {
                        $__cov = $__custOpt->getData();
                        if ($__custOpt->getGroupByType() == Option::OPTION_GROUP_SELECT) {
                            foreach ($__custOpt->getValues() as $__optValue) {
                                $__cov['optionValues'][] = $__optValue->getData();
                            }
                        }
                        $custOptAll[] = $__cov;
                    }
                }
                $prod->save();
                $prHlp->processAfterSave($prod);
                $prHlp->processUdmultiPost($prod, $v);
                if ($isNew) {
                    $prHlp->processNewConfigurable($prod, $v);
                }
                $prHlp->processQuickCreate($prod, $isNew);
                if (!$isNew && $canSaveCustOpt) {
                    if ($canSaveCustOpt) {
                        $custOptAllNew = [];
                        $prod->uclearOptions();
                        if ($prod->getHasOptions()) {
                            foreach ($prod->getProductOptionsCollection() as $option) {
                                $option->setProduct($prod);
                                $prod->addOption($option);
                            }
                        }
                        $__custOptAll = $prod->getOptions();
                        foreach ($__custOptAll as $__custOpt) {
                            $__cov = $__custOpt->getData();
                            if ($__custOpt->getGroupByType() == Option::OPTION_GROUP_SELECT) {
                                foreach ($__custOpt->getValues() as $__optValue) {
                                    $__cov['optionValues'][] = $__optValue->getData();
                                }
                            }
                            $custOptAllNew[] = $__cov;
                        }
                        if ($custOptAllNew!=$custOptAll) {
                            $this->_prodHlp->setNeedToUnpublish($prod, 'custom_options_changed');
                        }
                    }
                }
                $prHlp->reindexProduct($prod);
                $this->messageManager->addSuccess(__('Product has been saved'));
            } catch (\Exception $e) {
                $session->setUdprodFormData($r->getPost('product'));
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->_storeManager->setCurrentStore($oldStoreId);
        $this->_redirectAfterPost(@$prod);
    }
}
