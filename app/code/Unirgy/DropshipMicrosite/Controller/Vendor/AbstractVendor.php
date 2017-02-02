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
 * @package    Unirgy_DropshipMicrosite
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipMicrosite\Controller\Vendor;

use Magento\Captcha\Helper\Data as CaptchaHelperData;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\ScopeInterface;
use Unirgy\Dropship\Helper\Data as HelperData;

abstract class AbstractVendor extends Action
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var DesignInterface
     */
    protected $_viewDesign;

    /**
     * @var LayoutFactory
     */
    protected $_viewLayoutFactory;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var CaptchaHelperData
     */
    protected $_captchaHelperData;

    public function __construct(Context $context, 
        ScopeConfigInterface $scopeConfig,
        DesignInterface $viewDesignInterface, 
        LayoutFactory $viewLayoutFactory,
        CaptchaHelperData $captchaHelperData,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Unirgy\DropshipMicrosite\Helper\Data $micrositeHelper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_viewDesign = $viewDesignInterface;
        $this->_viewLayoutFactory = $viewLayoutFactory;
        $this->_hlp = $udropshipHelper;
        $this->_msHlp = $micrositeHelper;
        $this->_captchaHelperData = $captchaHelperData;
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct($context);
    }

    protected $_loginFormChecked = false;

    protected function _setTheme()
    {
        $theme = $this->_hlp->getScopeConfig('udropship/vendor/interface_theme');
        if (!empty($theme)) {
            $this->_viewDesign->setDesignTheme($theme);
        }
    }

    protected function _renderPage($handles=null, $active=null)
    {
        $this->_setTheme();
        $resultPage = $this->resultPageFactory->create(true);
        $resultPage->addHandle($handles);
        $resultPage->addHandle($resultPage->getDefaultLayoutHandle());

        $this->_hlp->getObj('\Magento\Framework\View\Page\Config')->addBodyClass('udropship-vendor');

        if ($active && ($head = $resultPage->getLayout()->getBlock('header'))) {
            $head->setActivePage($active);
        }
        $resultPage->getLayout()->initMessages();
        $resultPage->renderResult($this->_response);
        $html = $this->_response->getBody();
        //$newHtml = preg_replace('`<link[^>]+?media/styles.css[^>]+?/>`', '', $html);
        //$newHtml = preg_replace('`<link[^>]+?css/styles-m.css[^>]+?/>`', '', $newHtml);
        //$newHtml = preg_replace('`<link[^>]+?styles-l.css[^>]+?/>`', '', $newHtml);
        //$this->_response->setBody($newHtml);
        return $this->_response;
    }




    protected function _loginPostRedirect()
    {
        $this->_getVendorSession()->loginPostRedirect($this);
    }
    protected function _getVendorSession()
    {
        return ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
    }

    public function checkCaptcha()
    {
        return $this;
        if (!$this->_hlp->isModuleActive('Magento_Captcha')) return $this;
        ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->setData('umicrosite_registration_form_show_captcha',1);
        $formId = 'umicrosite_registration_form';
        $captchaModel = $this->_captchaHelperData->getCaptcha($formId);
        if ($captchaModel->isRequired()) {
            if (!$captchaModel->isCorrect($this->_getCaptchaString($this->getRequest(), $formId))) {
                throw new \Exception(__('Incorrect CAPTCHA.'));
            }
        }
        return $this;
    }

    protected function _getCaptchaString($request, $formId)
    {
        $captchaParams = $request->getPost(CaptchaHelperData::INPUT_NAME_FIELD_VALUE);
        return $captchaParams[$formId];
    }
}