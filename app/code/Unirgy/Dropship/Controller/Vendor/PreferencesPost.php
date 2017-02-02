<?php

namespace Unirgy\Dropship\Controller\Vendor;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Controller\Result\RedirectFactory;
use \Magento\Framework\View\DesignInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Magento\Framework\Registry;
use \Magento\Framework\View\LayoutFactory;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Backend\Model\View\Result\ForwardFactory;

class PreferencesPost extends AbstractVendor
{
    /**
     * @var \Unirgy\Dropship\Model\Config
     */
    protected $udropshipConfig;

    public function __construct(
        \Unirgy\Dropship\Model\Config $udropshipConfig,
        Context $context,
        ScopeConfigInterface $scopeConfig,
        DesignInterface $viewDesignInterface,
        StoreManagerInterface $storeManager,
        LayoutFactory $viewLayoutFactory,
        Registry $registry,
        ForwardFactory $resultForwardFactory,
        HelperData $helper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\HTTP\Header $httpHeader
    )
    {
        $this->udropshipConfig = $udropshipConfig;

        parent::__construct($context, $scopeConfig, $viewDesignInterface, $storeManager, $viewLayoutFactory, $registry, $resultForwardFactory, $helper, $resultPageFactory, $resultRawFactory, $httpHeader);
    }

    public function execute()
    {
        $defaultAllowedTags = $this->_hlp->getScopeConfig('udropship/vendor/preferences_allowed_tags');
        $session = $this->_hlp->session();
        $hlp = $this->_hlp;
        $r = $this->getRequest();
        if ($r->isPost()) {
            $p = $r->getPost();
            $hlp->processPostMultiselects($p);
            try {
                $v = $session->getVendor();
                foreach (array(
                    'vendor_name', 'vendor_attn', 'email', 'password', 'telephone',
                    'street', 'city', 'zip', 'country_id', 'region_id', 'region',
                    'billing_vendor_attn', 'billing_email', 'billing_telephone',
                    'billing_street', 'billing_city', 'billing_zip', 'billing_country_id', 'billing_region_id', 'billing_region'
                ) as $f) {
                    if (array_key_exists($f, $p)) $v->setData($f, $p[$f]);
                }
                foreach ($this->udropshipConfig->getField() as $code=>$node) {
                    if (!isset($p[$code])) {
                        continue;
                    }
                    $param = $p[$code];
                    if (is_array($param)) {
                        foreach ($param as $key=>$val) {
                            $param[$key] = strip_tags($val, $defaultAllowedTags);
                        }
                    }
                    else {
                        $allowedTags = $defaultAllowedTags;
                        if (@$node['filter_input'] && ($stripTags = @$node['filter_input']['strip_tags']) && isset($stripTags['allowed'])) {
                            $allowedTags = (string)$stripTags['allowed'];
                        }
                        if ($allowedTags && @$node['type'] != 'wysiwyg') {
                            $param = strip_tags($param, $allowedTags);
                        }

                        if (@$node['filter_input'] && ($replace = @$node['filter_input']['preg_replace']) && isset($replace['from']) && isset($replace['to'])) {
                            $param = preg_replace((string)$replace['from'], (string)$replace['to'], $param);
                        }
                    } // end code injection protection
                    $v->setData($code, $param);
                }
                $this->_eventManager->dispatch('udropship_vendor_preferences_save_before', array('vendor'=>$v, 'post_data'=>&$p));
                $v->save();
#echo "<pre>"; print_r($v->debug()); exit;
                $this->messageManager->addSuccess(__('Settings has been saved'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $this->resultRedirectFactory->create()->setPath('udropship/vendor/preferences');
    }
}
