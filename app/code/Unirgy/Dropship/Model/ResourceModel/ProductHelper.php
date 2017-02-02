<?php

namespace Unirgy\Dropship\Model\ResourceModel;

use \Magento\Catalog\Model\Product;
use \Magento\Framework\DataObject;

class ProductHelper extends \Magento\Catalog\Model\ResourceModel\Product
{
    public function multiUpdateAttributes($attrData, $storeId)
    {
        $hlp = \Magento\Framework\App\ObjectManager::getInstance()->get('\Unirgy\Dropship\Helper\Data');
        $object = new DataObject();
        $object->setStoreId($storeId);
        $rowIdField = $hlp->rowIdField();

        $this->getConnection()->beginTransaction();
        try {
            $i = 0;
            foreach ($attrData as $entityId => $_attrData) {
                foreach ($_attrData as $attrCode => $value) {
                    $attribute = $this->getAttribute($attrCode);
                    if (!$attribute->getAttributeId()) {
                        continue;
                    }
                    $i++;
                    $object->setId($entityId);
                    $object->setData($rowIdField,$entityId);
                    $this->_saveAttributeValue($object, $attribute, $value);
                    if ($i % 1000 == 0) {
                        $this->_processAttributeValues();
                    }
                    $this->_processAttributeValues();
                }
            }
            $this->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            throw $e;
        }

        return $this;
    }
}
