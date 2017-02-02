<?php

namespace Unirgy\DropshipTierShipping\Controller\Vendor;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class RatesPost
 * @package Unirgy\DropshipTierShipping\Controller\Vendor
 */
class RatesPost extends AbstractVendor
{
    public function execute()
    {
        $session = $this->_hlp->session();
        /** @var Http $r */
        $r = $this->getRequest();
        if ($r->isPost()) {
            $p = $r->getPost();
            try {
                $v = $session->getVendor();
                $v->setTiershipRates($p['tiership_rates']);
                $v->save();
                $this->messageManager->addSuccess('Rates has been saved');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $this->_redirect('udtiership/vendor/rates');
    }
}
