<?php


namespace Unirgy\DropshipVendorProduct\Model\SystemConfig\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Unirgy\Dropship\Helper\Data as HelperData;

class SyQuickCreateConfig extends Value
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
        $value = $this->_unserialize($value);
        if (is_array($value)) {
            unset($value['$$ROW']);
            $colDef = [
                'fields_extra'=>[],
                'required_fields'=>[],
            ];
            foreach (['columns_def']
                as $colKey
            ) {
                if (is_array(@$value[$colKey])) {
                    unset($value[$colKey]['$ROW']);
                    usort($value[$colKey], [$this, 'sortBySortOrder']);
                    foreach ($value[$colKey] as $r) {
                       $colDef[substr($colKey,0,-4)][] = $r['column_field'];
                       $colDef['fields_extra'][$r['column_field']] = [];
                       if (!empty($r['is_required'])) {
                           $colDef['required_fields'][] = $r['column_field'];
                       }
                    }
                }
            }
            $value = array_merge($value, $colDef);
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
        return parent::afterLoad();
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
        if ($a['sort_order']<$b['sort_order']) {
            return -1;
        } elseif ($a['sort_order']>$b['sort_order']) {
            return 1;
        }
        return 0;
    }

    protected function _serialize($value)
    {
        return $this->_helperData->serialize($value);
    }
    protected function _unserialize($value)
    {
        return $this->_helperData->unserialize($value);
    }
}
