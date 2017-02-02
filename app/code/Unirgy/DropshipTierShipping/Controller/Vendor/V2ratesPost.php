<?php

namespace Unirgy\DropshipTierShipping\Controller\Vendor;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class V2ratesPost
 * @package Unirgy\DropshipTierShipping\Controller\Vendor
 */
class V2ratesPost extends AbstractVendor
{
    public function execute()
    {
        $session = $this->_hlp->session();
        /** @var \Unirgy\DropshipTierShipping\Helper\Data $tsHlp */
        $tsHlp = $this->_hlp->getObj('\Unirgy\DropshipTierShipping\Helper\Data');
        /** @var Http $r */
        $r = $this->getRequest();
        if ($r->isPost()) {
            $p = $r->getPost();
            try {
                $v = $session->getVendor();
                $v->setData('tiership_v2_rates', @$p['tiership_v2_rates']);
                $v->setData('tiership_v2_simple_rates', @$p['tiership_v2_simple_rates']);
                $v->setData('tiership_v2_simple_cond_rates', @$p['tiership_v2_simple_cond_rates']);
                $tsHlp->saveVendorV2Rates($v->getId(), $v->getData('tiership_v2_rates'));
                $tsHlp->saveVendorV2SimpleRates($v->getId(), $v->getData('tiership_v2_simple_rates'));
                $tsHlp->saveVendorV2SimpleCondRates($v->getId(), $v->getData('tiership_v2_simple_cond_rates'));
                $this->messageManager->addSuccess('Rates has been saved');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $this->_redirect('udtiership/vendor/v2rates');
    }
}
