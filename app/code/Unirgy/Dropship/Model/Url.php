<?php

namespace Unirgy\Dropship\Model;

use \Magento\Framework\Registry;

class Url extends \Magento\Framework\Url
{
    /**
     * @var Registry
     */
    protected $_registry;

    public function __construct(
        Registry $registry,
        \Magento\Framework\App\Route\ConfigInterface $routeConfig,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Url\SecurityInfoInterface $urlSecurityInfo,
        \Magento\Framework\Url\ScopeResolverInterface $scopeResolver,
        \Magento\Framework\Session\Generic $session,
        \Magento\Framework\Session\SidResolverInterface $sidResolver,
        \Magento\Framework\Url\RouteParamsResolverFactory $routeParamsResolverFactory,
        \Magento\Framework\Url\QueryParamsResolverInterface $queryParamsResolver,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        $scopeType,
        array $data = []
    )
    {
        $this->_registry = $registry;
        parent::__construct($routeConfig,
            $request,
            $urlSecurityInfo,
            $scopeResolver,
            $session,
            $sidResolver,
            $routeParamsResolverFactory,
            $queryParamsResolver,
            $scopeConfig,
            $scopeType,
            $data
        );
    }

    public function getStore()
    {
        if (!$this->hasData('store')) {
            $this->setStore(null);
        }
        return $this->_registry->registry('url_store')
            ? $this->_registry->registry('url_store')
            : $this->_getData('store');
    }
}