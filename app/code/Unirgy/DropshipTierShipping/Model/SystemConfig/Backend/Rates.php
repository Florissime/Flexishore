<?php


namespace Unirgy\DropshipTierShipping\Model\SystemConfig\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

class Rates extends Value
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    public function __construct(Context $context, 
        Registry $registry, 
        ScopeConfigInterface $config, 
        TypeListInterface $cacheTypeList, 
        HelperData $helperData, 
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        array $data = [])
    {
        $this->_helperData = $helperData;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function setValue($value)
    {
        if (is_array($value)) {
            unset($value['$ROW']);
            unset($value['$$ROW']);
        }
        $this->setData('value', $value);
        return $this;
    }
    public function beforeSave()
    {
        $value = $this->getValue();
        $this->_helperData->saveV2Rates($value);
        $this->setValue('');
        return parent::beforeSave();
    }

    protected function _afterLoad()
    {
    }

    public function sortBySortOrder($a, $b)
    {
        if (@$a['sort_order']<@$b['sort_order']) {
            return -1;
        } elseif (@$a['sort_order']>@$b['sort_order']) {
            return 1;
        }
        return 0;
    }
}
