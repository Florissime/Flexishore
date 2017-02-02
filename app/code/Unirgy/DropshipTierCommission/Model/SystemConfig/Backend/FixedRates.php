<?php

namespace Unirgy\DropshipTierCommission\Model\SystemConfig\Backend;

use Magento\Config\Model\Config\Backend\Serialized;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Filter\LocalizedToNormalized;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class FixedRates extends Serialized
{
    /**
     * @var Resolver
     */
    protected $_localeResolver;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Resolver $localeResolver,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_localeResolver = $localeResolver;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $udtcFixedConfig = $this->getValue();
        if (is_array($udtcFixedConfig) && isset($udtcFixedConfig['edit'])) {
            if (isset($udtcFixedConfig['limit'])) {
                reset($udtcFixedConfig['limit']);
                $firstTitleKey = key($udtcFixedConfig['limit']);
                if (!is_numeric($firstTitleKey)) {
                    $newudtcFixedConfig = array();
                    $filter = new LocalizedToNormalized(
                        ['locale' => $this->_localeResolver->getLocale()]
                    );
                    foreach ($udtcFixedConfig['limit'] as $_k => $_t) {
                        if (($_limit = $filter->filter($udtcFixedConfig['limit'][$_k]))
                            && false !== ($_value = $filter->filter($udtcFixedConfig['value'][$_k]))
                        ) {
                            $_limit = is_numeric($_limit) ? $_limit : '*';
                            $_sk = is_numeric($_limit) ? $_limit : '9999999999';
                            $_sk = 'str' . str_pad((string)$_sk, 20, '0', STR_PAD_LEFT);
                            $newudtcFixedConfig[$_sk] = array(
                                'limit' => $_limit,
                                'value' => $_value,
                            );
                        }
                    }
                    ksort($newudtcFixedConfig);
                    $newudtcFixedConfig = array_values($newudtcFixedConfig);
                    $this->setValue(array_values($newudtcFixedConfig));
                }
            } else {
                $this->setValue(array());
            }
        }
        return parent::beforeSave();
    }
}
