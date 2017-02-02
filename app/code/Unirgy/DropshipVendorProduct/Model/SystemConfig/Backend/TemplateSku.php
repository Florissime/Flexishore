<?php


namespace Unirgy\DropshipVendorProduct\Model\SystemConfig\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Unirgy\DropshipVendorProduct\Helper\Data as HelperData;

class TemplateSku extends Value
{
    /**
     * @var ValueFactory
     */
    protected $_configValueFactory;

    /**
     * @var HelperData
     */
    protected $_prodHlp;

    /** @var \Unirgy\Dropship\Helper\Data */
    protected $_hlp;

    public function __construct(Context $context, 
        Registry $registry, 
        ScopeConfigInterface $config, 
        TypeListInterface $cacheTypeList, 
        ValueFactory $configValueFactory, 
        HelperData $helperData,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        array $data = [])
    {
        $this->_configValueFactory = $configValueFactory;
        $this->_prodHlp = $helperData;
        $this->_hlp = $udropshipHelper;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    protected $_myOrigValue;
    public function setMyOrigValue($value)
    {
        $this->_myOrigValue = $value;
        return $this;
    }
    public function getMyOrigValue()
    {
        if (null === $this->_myOrigValue) {
            $origData = $this->_hlp->rHlp()->loadDbColumns(
                $this->_configValueFactory->create(),
                ['path'=>'udprod/template_sku/value'],
                ['value'],
                "scope='default' and scope_id=0"
            );
            $this->_myOrigValue = [];
            if (!empty($origData)) {
                reset($origData);
                $value = current($origData);
                $value = !isset($value['value']) ? [] : $value['value'];
                if (!is_array($value)) {
                    $value = unserialize($value);
                }
                $this->_myOrigValue = $value;
            }
        }
        return $this->_myOrigValue;
    }
    public function setValue($value)
    {
        $origValue = $this->getMyOrigValue();
        $origValue = empty($origValue) ? [] : $origValue;
        if (!is_array($origValue)) {
            $origValue = unserialize($origValue);
        }
        $value = empty($value) ? [] : $value;
        if (!is_array($value)) {
            $value = unserialize($value);
        }
        if (is_array($value)) {
            foreach ($value as $sIdEnc => $_val) {
                if (isset($_val['__id__']) && is_array(@$_val['cfg_attributes_def'])) {
                    $sId = $this->_hlp->urlDecode($_val['__id__']);
                    unset($_val['cfg_attributes_def']['$ROW']);
                    usort($_val['cfg_attributes_def'], [$this, 'sortBySortOrder']);
                    $cfgAttrs = [];
                    $iiAttrs = [];
                    foreach ($_val['cfg_attributes_def'] as $cad) {
                        $cfgAttrs[] = $cad['attribute_id'];
                        if ($cad['identify_image']) {
                            $iiAttrs[] = $cad['attribute_id'];
                        }
                    }
                    $_val['cfg_attributes'] = $cfgAttrs;
                    $_val['cfg_identify_image'] = $iiAttrs;
                    $origValue[$sId] = $_val;
                }
            }
        }
        $this->setData('value', $origValue);
        $this->setMyOrigValue($origValue);
        return $this;
    }
    public function afterLoad()
    {
        if (!is_array($this->getValue())) {
            $value = $this->getValue();
            $this->setData('value', empty($value) ? false : unserialize($value));
        }
        return parent::afterLoad();
    }

    public function beforeSave()
    {
        if (is_array($this->getValue())) {
            $this->setData('value', serialize($this->getValue()));
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
}
