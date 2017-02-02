<?php

namespace Unirgy\DropshipTierCommission\Controller\Vendor;

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
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Session;

/**
 * Class RatesPost
 * @package Unirgy\DropshipTierCommission\Controller\Vendor
 */
class RatesPost extends \Unirgy\Dropship\Controller\Vendor\AbstractVendor
{
    public function execute()
    {
        $session = $this->_hlp->session();
        $hlp = $this->_hlp;
        $r = $this->getRequest();
        if ($r->isPost()) {
            $p = $r->getPost();
            try {
                $v = $session->getVendor();
                $v->setTiercomRates($p['tiercom_rates']);
                $v->save();
#echo "<pre>"; print_r($v->debug()); exit;
                $this->messageManager->addSuccess('Rates has been saved');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->_redirect('udtiercom/vendor/rates');
    }
}
