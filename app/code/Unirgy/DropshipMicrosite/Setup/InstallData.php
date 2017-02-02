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

namespace Unirgy\DropshipMicrosite\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    const MEDIUMTEXT_SIZE = 16777216;
    const TEXT_SIZE = 65536;

    protected $rulesFactory;

    public function __construct(
        \Magento\Authorization\Model\RulesFactory $rulesFactory
    )
    {
        $this->rulesFactory = $rulesFactory;
    }


    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $conn = $setup->getConnection();
        $t = $setup->getTable('authorization_role');
        $roleId = $conn->fetchOne("select role_id from {$t} where role_name='Dropship Vendor'");
        if (!$roleId) {
            $conn->insert($t, array('tree_level'=>1, 'role_type'=>'G', 'role_name'=>'Dropship Vendor', 'user_type'=>2));
            $roleId = $conn->lastInsertId($t);

            $rules = $this->rulesFactory->create();
            $rules->setResources(['Magento_Catalog::catalog','Magento_Catalog::catalog_inventory','Magento_Catalog::products']);
            $rules->setRoleId($roleId)->saveRel();
        }

        $ut = $setup->getTable('admin_user');
        $vendors = $conn->fetchAll("select * from {$setup->getTable('udropship_vendor')}");
        foreach ($vendors as $v) {
            if ($conn->fetchOne("select user_id from {$ut} where username=?", $v['email'])) {
                continue;
            }
            $conn->insert($ut, array(
                'firstname' => $v['vendor_name'],
                'lastname'  => $v['vendor_attn'],
                'email'     => $v['email'],
                'username'  => $v['email'],
                'password'  => $v['password_hash'],
                'created'   => (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT),
                'is_active' => 1,
                'udropship_vendor' => $v['vendor_id'],
            ));
            $userId = $conn->lastInsertId($ut);
            $conn->insert($t, array(
                'parent_id'=>$roleId,
                'tree_level'=>2,
                'role_type'=>'U',
                'user_id'=>$userId,
                'user_type'=>2,
                'role_name'=>$v['vendor_name'],
            ));
        }
    }
}