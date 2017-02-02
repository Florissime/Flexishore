<?php

namespace Unirgy\DropshipVendorProduct\Controller\Vendor;

use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;

class Products extends AbstractVendor
{
    public function execute()
    {
        $session = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
        $session->setUdprodLastGridUrl($this->_url->getUrl('*/*/*', ['__vp'=>true,'_current'=>true]));
        $this->_renderPage(null, 'udprod');
    }
}
