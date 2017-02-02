<?php

namespace Unirgy\DropshipVendorProduct\Controller\Vendor;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Layout;
use Magento\Store\Model\StoreManagerInterface;

class CfgQuickCreateAttribute extends AbstractVendor
{
    public function execute()
    {
        $session = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
        $oldStoreId = $this->_storeManager->getStore()->getId();
        try {
            $this->_setTheme();
            $resultPage = $this->resultPageFactory->create(true);
            $resultPage->addHandle($resultPage->getDefaultLayoutHandle());
            $resultPage->getLayout()->publicBuild();
            $prodBlock = $resultPage->getLayout()->getBlock('udprod.edit');
            $cfgEl = $prodBlock->getForm()->getElement('_cfg_quick_create');
            $__value = $this->getRequest()->getParam('cfg_attr_values');
            $cfgEl->setCfgAttributeValueTuple(explode(',',$__value));
            return $this->_resultRawFactory->create()->setContents(
                $cfgEl->getHtml()
            );
        } catch (\Exception $e) {
            $this->_storeManager->setCurrentStore($oldStoreId);
            $this->returnResult([
                'error'=>true,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
