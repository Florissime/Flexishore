<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    \Unirgy\Dropship
 * @copyright  Copyright (c) 2015-2016 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\Dropship\Model\ResourceModel;

use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use \Magento\Framework\Model\ResourceModel\Db\Context;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Shipping extends AbstractDb
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var String
     */
    protected $_strHlp;

    public function __construct(
        HelperData $helper,
        \Magento\Framework\Stdlib\StringUtils $stringHelper,
        Context $context
    )
    {
        $this->_hlp = $helper;
        $this->_strHlp = $stringHelper;

        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('udropship_shipping', 'shipping_id');
    }

    protected function _afterLoad(AbstractModel $object)
    {
        parent::_afterLoad($object);

        $id = $object->getId();
        if (!$id) {
            return;
        }

        $read = $this->getConnection();

        $table = $this->getTable('udropship_shipping_website');
        $select = $read->select()->from($table)->where($table.'.shipping_id=?', $id);
        if ($result = $read->fetchAll($select)) {
            foreach ($result as $row) {
                $websites = $object->getWebsiteIds();
                if (!$websites) $websites = array();
                $websites[] = $row['website_id'];
                $object->setWebsiteIds($websites);
            }
        }

        $table = $this->getTable('udropship_shipping_method');
        $select = $read->select()->from($table)->where($table.'.shipping_id=?', $id);
        $tblColumns = $this->getConnection()->describeTable($table);
        if (isset($tblColumns['profile'])) {
            $select->order(new \Zend_Db_Expr("{$table}.profile='default'"));
        }
        if (isset($tblColumns['sort_order'])) {
            $select->order(new \Zend_Db_Expr("{$table}.sort_order"));
        }
        if ($result = $read->fetchAll($select)) {
            foreach ($result as $row) {
                $methods = $object->getSystemMethodsByProfile();
                $fullMethods = $object->getFullSystemMethodsByProfile();
                if (!$methods) $methods = array();
                $profile = 'default';
                if (!empty($row['profile'])
                    && $this->_hlp->isUdsprofileActive()
                    && $this->_hlp->udsprofileHlp()->hasProfile($row['profile'])
                ) {
                    $profile=$row['profile'];
                }
                $methods[$profile][$row['carrier_code']][$row['method_code']] = $row['method_code'];
                $object->setSystemMethodsByProfile($methods);
                $fullMethods[$profile][] = $row;
                $object->setFullSystemMethodsByProfile($fullMethods);
            }
            if ($this->_hlp->isUdsprofileActive()) {
                foreach ($result as $row) {
                    $methods = $object->getSystemMethodsByProfile();
                    if (!$methods || empty($row['est_use_custom'])) $methods = array();
                    $profile = 'default';
                    if (!empty($row['profile'])
                        && $this->_hlp->isUdsprofileActive()
                        && $this->_hlp->udsprofileHlp()->hasProfile($row['profile'])
                    ) {
                        $profile=$row['profile'];
                    }
                    $methods[$profile][$row['est_carrier_code']][$row['est_method_code']] = $row['est_method_code'];
                    $object->setSystemMethodsByProfile($methods);
                }
            }
            $object->setSystemMethods(@$methods['default']);
        }
    }

    protected function _afterSave(AbstractModel $object)
    {
        parent::_afterSave($object);

        $write = $this->getConnection();

        $table = $this->getTable('udropship_shipping_website');
        $write->delete($table, $write->quoteInto('shipping_id=?', $object->getId()));
        $websiteIds = $object->getWebsiteIds();
        if (in_array(0, $websiteIds)) {
            $websiteIds = array(0);
        }
        foreach ($websiteIds as $wId) {
            $write->insert($table, array('shipping_id'=>$object->getId(), 'website_id'=>(int)$wId));
        }

        $table = $this->getTable('udropship_shipping_method');
        if ($object->getPostedSystemMethods()) {
            $write->delete($table, $write->quoteInto('shipping_id=?', $object->getId()));
            foreach ($object->getPostedSystemMethods() as $c=>$m) {
                if (!$m) continue;
                $write->insert($table, array('shipping_id'=>$object->getId(), 'carrier_code'=>$c, 'method_code'=>$m));
            }
        }

        if ($object->hasStoreTitles()) {
            $this->saveStoreTitles($object->getId(), $object->getStoreTitles());
        }
    }

    public function saveStoreTitles($shippingId, $titles)
    {
        $deleteByStoreIds = array();
        $table   = $this->getTable('udropship_shipping_title');
        $adapter = $this->getConnection();

        $data    = array();
        foreach ($titles as $storeId => $title) {
            if ($this->_strHlp->strlen($title)) {
                $data[] = array('shipping_id' => $shippingId, 'store_id' => $storeId, 'title' => $title);
            } else {
                $deleteByStoreIds[] = $storeId;
            }
        }

        $adapter->beginTransaction();
        try {
            if (!empty($data)) {
                $adapter->insertOnDuplicate(
                    $table,
                    $data,
                    array('title')
                );
            }

            if (!empty($deleteByStoreIds)) {
                $adapter->delete($table, array(
                    'shipping_id=?'       => $shippingId,
                    'store_id IN (?)' => $deleteByStoreIds
                ));
            }
        } catch (\Exception $e) {
            $adapter->rollback();
            throw $e;

        }
        $adapter->commit();

        return $this;
    }

    public function getStoreTitles($shippingId)
    {
        $select = $this->getConnection()->select()
            ->from($this->getTable('udropship_shipping_title'), array('store_id', 'title'))
            ->where('shipping_id = :shipping_id');
        return $this->getConnection()->fetchPairs($select, array(':shipping_id' => $shippingId));
    }

    public function getStoreTitle($shippingId, $storeId)
    {
        $select = $this->getConnection()->select()
            ->from($this->getTable('udropship_shipping_title'), 'title')
            ->where('shipping_id = :shipping_id')
            ->where('store_id IN(0, :store_id)')
            ->order('store_id DESC');
        return $this->getConnection()->fetchOne($select, array(':shipping_id' => $shippingId, ':store_id' => $storeId));
    }

}