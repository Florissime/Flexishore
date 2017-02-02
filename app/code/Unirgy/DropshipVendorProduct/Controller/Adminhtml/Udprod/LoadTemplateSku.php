<?php

namespace Unirgy\DropshipVendorProduct\Controller\Adminhtml\Udprod;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form;
use Magento\Framework\View\Layout;
use Magento\Store\Model\ScopeInterface;

class LoadTemplateSku extends AbstractUdprod
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    protected $_formFactory;

    public function __construct(
        Context $context,
        ScopeConfigInterface $configScopeConfigInterface,
        \Magento\Framework\Data\FormFactory $formFactory
    )
    {
        $this->_scopeConfig = $configScopeConfigInterface;
        $this->_formFactory = $formFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $typeOfProduct = $this->getRequest()->getParam('type_of_product');
        $_form = $this->_formFactory->create();
        $tplSku = $this->_scopeConfig->getValue('udprod/template_sku/value', ScopeInterface::SCOPE_STORE);
        $tplSku = empty($tplSku) ? [] : $tplSku;
        if (!is_array($tplSku)) {
            $tplSku = unserialize($tplSku);
        }
        $tplSkuEl = $_form->addField('udprod_template_sku_value', 'select', [
            'name'=>'groups[template_sku][fields][value][value]',
            'label'=>__('Template Sku'),
            'value'=>$tplSku,
        ]);
        $renderer = $this->_view->getLayout()->createBlock('\Unirgy\DropshipVendorProduct\Block\Adminhtml\SystemConfigField\TemplateSku');
        $renderer->setTypeOfProduct($typeOfProduct);
        return $this->_response->setBody($renderer->getElementHtml($tplSkuEl));
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Unirgy_DropshipVendorProduct::system_config');
    }
}
