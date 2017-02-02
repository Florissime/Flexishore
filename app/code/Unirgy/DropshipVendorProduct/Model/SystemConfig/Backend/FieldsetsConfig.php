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

class FieldsetsConfig extends Value
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(Context $context, 
        Registry $registry, 
        ScopeConfigInterface $config, 
        TypeListInterface $cacheTypeList, 
        HelperData $helperData, 
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        array $data = [])
    {
        $this->_hlp = $helperData;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function setValue($value)
    {
        $value = $this->_unserialize($value);
        if (is_array($value)) {
            unset($value['$$ROW']);
            usort($value, [$this, 'sortBySortOrder']);
            foreach ($value as &$_val) {
                $colDef = [
                    'fields_extra'=>[],
                    'required_fields'=>[],
                ];
                foreach (['top_columns_def','bottom_columns_def','left_columns_def','right_columns_def']
                    as $colKey
                ) {
                    if (is_array(@$_val[$colKey])) {
                        unset($_val[$colKey]['$ROW']);
                        usort($_val[$colKey], [$this, 'sortBySortOrder']);
                        foreach ($_val[$colKey] as $rKey=>$r) {
                            if (!isset($r['column_field'])) {
                                unset($_val[$colKey][$rKey]);
                                continue;
                            }
                            $colDef[substr($colKey,0,-4)][] = $r['column_field'];
                            $colDef['fields_extra'][$r['column_field']] = [
                               'use_limit_type' => @$r['use_limit_type'],
                               'limit_type' => @$r['limit_type'],
                            ];
                            if (!empty($r['is_required'])) {
                               $colDef['required_fields'][] = $r['column_field'];
}
                        }
                    }
                }
                $_val = array_merge($_val, $colDef);
            }
            unset($_val);
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
        return $this->_hlp->serialize($value);
    }
    protected function _unserialize($value)
    {
        return $this->_hlp->unserialize($value);
    }
}
