<?php

namespace Unirgy\Dropship\Model\SystemConfig\Backend;

use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Config\Value;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Helper\Data as HelperData;

class RowsSortable extends Value
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(
        HelperData $helperData,
        Context $context,
        Registry $registry, 
        ScopeConfigInterface $config, 
        TypeListInterface $cacheTypeList, 
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        array $data = []
    )
    {
        $this->_hlp = $helperData;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function setValue($value)
    {
        $value = $this->_unserialize($value);
        if (is_array($value)) {
            unset($value['$ROW']);
            usort($value, array($this, 'sortBySortOrder'));
        }
        $this->setData('value', $value);
        return $this;
    }
    public function afterLoad()
    {
        if (!is_array($this->getValue())) {
            $value = $this->getValue();
            $this->setData('value', $this->_unserialize($value));
        }
        parent::afterLoad();
    }

    public function beforeSave()
    {
        if (is_array($this->getValue())) {
            $this->setData('value', $this->_serialize($this->getValue()));
        }
        return parent::beforeSave();
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

    protected function _serialize($value)
    {
        return $this->_hlp->serialize($value);
    }
    protected function _unserialize($value)
    {
        return $this->_hlp->unserialize($value);
    }
}
