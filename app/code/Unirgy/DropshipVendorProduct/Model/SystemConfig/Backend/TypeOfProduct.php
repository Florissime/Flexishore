<?php


namespace Unirgy\DropshipVendorProduct\Model\SystemConfig\Backend;

use Magento\Config\Model\Config\Backend\Serialized;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class TypeOfProduct extends Serialized
{
    /**
     * @var Resolver
     */
    protected $_localeResolver;

    public function __construct(Context $context, 
        Registry $registry, 
        ScopeConfigInterface $config, 
        TypeListInterface $cacheTypeList, 
        Resolver $localeResolver, 
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        array $data = [])
    {
        $this->_localeResolver = $localeResolver;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $udprodTypeOfProduct = $this->getValue();
        if (is_array($udprodTypeOfProduct) && !empty($udprodTypeOfProduct)
            && !empty($udprodTypeOfProduct['type_of_product']) && is_array($udprodTypeOfProduct['type_of_product'])
        ) {
            reset($udprodTypeOfProduct['type_of_product']);
            $firstTitleKey = key($udprodTypeOfProduct['type_of_product']);
            if (!is_numeric($firstTitleKey)) {
                $newudprodTypeOfProduct = [];
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_localeResolver->getLocale()]
                );
                foreach ($udprodTypeOfProduct['type_of_product'] as $_k => $_t) {
                    $newudprodTypeOfProduct[] = [
                        'type_of_product' => $_t,
                        'attribute_set' => $udprodTypeOfProduct['attribute_set'][$_k],
                    ];
                }
                $this->setValue($newudprodTypeOfProduct);
            }
        }
        return parent::beforeSave();
    }
}
