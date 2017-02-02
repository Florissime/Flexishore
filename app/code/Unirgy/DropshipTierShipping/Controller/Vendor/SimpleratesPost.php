<?php

namespace Unirgy\DropshipTierShipping\Controller\Vendor;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;

/**
 * Class SimpleratesPost
 * @package Unirgy\DropshipTierShipping\Controller\Vendor
 */
class SimpleratesPost extends AbstractVendor
{
    public function execute()
    {
        $session =$this->_hlp->session();
        /** @var Http $r */
        $r = $this->getRequest();
        if ($r->isPost()) {
            $p = $r->getPost();
            try {
                $v = $session->getVendor();
                $v->setTiershipSimpleRates($p['tiership_simple_rates']);
                $v->save();
#echo "<pre>"; print_r($v->debug()); exit;
                $this->messageManager->addSuccess('Rates has been saved');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->_redirect('udtiership/vendor/simplerates');
    }
}
