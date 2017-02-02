<?php

namespace Unirgy\Dropship\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;

class Config
{
    public function aroundGetValue(\Magento\Framework\App\Config\ScopeConfigInterface $subject,
        \Closure $proceed,
        $path = null,
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null)
    {
        if ($this->hasModifiedConfig($path, $scope, $scopeCode)) {
            return $this->getModifiedConfig($path, $scope, $scopeCode);
        } else {
            return $proceed($path, $scope, $scopeCode);
        }
    }
    protected $_modifiedConfig = [];
    public function hasModifiedConfig($path = null, $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        $configPath = $this->getFullConfigPath($path, $scope, $scopeCode);
        return array_key_exists($configPath, $this->_modifiedConfig);
    }
    public function getModifiedConfig($path = null, $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        $configPath = $this->getFullConfigPath($path, $scope, $scopeCode);
        return @$this->_modifiedConfig[$configPath];
    }
    public function setModifiedConfig($value, $path = null, $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        $configPath = $this->getFullConfigPath($path, $scope, $scopeCode);
        return @$this->_modifiedConfig[$configPath] = $value;
    }

    public function getFullConfigPath($path = null, $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        if ($scope === 'store') {
            $scope = 'stores';
        } elseif ($scope === 'website') {
            $scope = 'websites';
        }
        $configPath = $scope;
        if ($scope !== 'default') {
            if (is_numeric($scopeCode) || $scopeCode === null) {
                if (class_exists('\Magento\Framework\App\Config\ScopeCodeResolver',false)) {
                    $scopeCodeResolver = ObjectManager::getInstance()->get(\Magento\Framework\App\Config\ScopeCodeResolver::class);
                    $scopeCode = $scopeCodeResolver->resolve($scope, $scopeCode);
                } else {
                    $scopeCode = $this->_getScopeCode($scope, $scopeCode);
                }
            } else if ($scopeCode instanceof \Magento\Framework\App\ScopeInterface) {
                $scopeCode = $scopeCode->getCode();
            }
            if ($scopeCode) {
                $configPath .= '/' . $scopeCode;
            }
        }
        if ($path) {
            $configPath .= '/' . $path;
        }
        return $configPath;
    }
    protected function _getScopeCode($scopeType, $scopeCode)
    {
        /**@var \Magento\Framework\App\ScopeResolverPool $scopeResolverPool */
        $scopeResolverPool = ObjectManager::getInstance()->get('\Magento\Framework\App\ScopeResolverPool');
        if (($scopeCode === null || is_numeric($scopeCode))
            && $scopeType !== ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        ) {
            $scopeResolver = $scopeResolverPool->get($scopeType);
            $scopeCode = $scopeResolver->getScope($scopeCode);
        }

        if ($scopeCode instanceof \Magento\Framework\App\ScopeInterface) {
            $scopeCode = $scopeCode->getCode();
        }

        return $scopeCode;
    }
}
