<?php


namespace Unirgy\DropshipTierShipping\Model\SystemConfig\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Unirgy\DropshipTierShipping\Model\DeliveryTypeFactory;

class DeliveryType extends Value
{
    /**
     * @var DeliveryTypeFactory
     */
    protected $_deliveryTypeFactory;
    protected $_hlp;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        Context $context,
        Registry $registry, 
        ScopeConfigInterface $config, 
        TypeListInterface $cacheTypeList,
        DeliveryTypeFactory $deliveryTypeFactory,
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        array $data = [])
    {
        $this->_deliveryTypeFactory = $deliveryTypeFactory;
        $this->_hlp = $udropshipHelper;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function setValue($value)
    {
        if (is_array($value)) {
            unset($value['$ROW']);
        }
        $this->setData('value', $value);
        return $this;
    }
    public function beforeSave()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            unset($value['$ROW']);
            $rHlp = $this->_hlp->rHlp();
            $conn = $rHlp->getConnection();
            $dtTable = $rHlp->getTable('udtiership_delivery_type');
            $fieldsData = $rHlp->myPrepareDataForTable($dtTable, [], true);
            $fields = array_keys($fieldsData);
            $existing = $rHlp->loadDbColumns($this->_deliveryTypeFactory->create(), true, $fields);
            $insert = [];
            foreach ($value as $v) {
                if (empty($v['delivery_title'])) continue;
                if (!empty($v['delivery_type_id'])) {
                    unset($existing[$v['delivery_type_id']]);
                } else {
                    $v['delivery_type_id'] = null;
                }
                $insert[] = $rHlp->myPrepareDataForTable($dtTable, $v, true);
            }
            if (!empty($insert)) {
                $rHlp->multiInsertOnDuplicate($dtTable, $insert);
            }
            if (!empty($existing)) {
                $conn->delete($dtTable, ['delivery_type_id in (?)'=>array_keys($existing)]);
            }
        }
        $this->setValue('');
        return parent::beforeSave();
    }

    protected function _afterLoad()
    {
        $rHlp = $this->_hlp->rHlp();
        $conn = $rHlp->getConnection();
        $dtTable = $rHlp->getTable('udtiership_delivery_type');
        $fieldsData = $rHlp->myPrepareDataForTable('udtiership_delivery_type', [], true);
        $fields = array_keys($fieldsData);
        $existing = $rHlp->loadDbColumns($this->_deliveryTypeFactory->create(), true, $fields);
        usort($existing, [$this, 'sortBySortOrder']);
        $this->setValue($existing);
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
