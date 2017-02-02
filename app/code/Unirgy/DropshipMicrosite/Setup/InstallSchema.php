<?php

namespace Unirgy\DropshipMicrosite\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema  implements InstallSchemaInterface
{
    const MEDIUMTEXT_SIZE = 16777216;
    const TEXT_SIZE = 65536;

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        $adminTable = $installer->getTable('admin_user');
        $connection->modifyColumn($adminTable, 'username', ['TYPE'=>Table::TYPE_TEXT, 'LENGTH'=>128, 'nullable' => true, 'COMMENT'=>'User Login']);
        $connection->addColumn($adminTable, 'udropship_vendor', ['TYPE'=>Table::TYPE_INTEGER, 'unsigned'=>true, 'COMMENT'=>'Vendor ID']);

        $vendorTable = $installer->getTable('udropship_vendor');
        $connection->addColumn($vendorTable, 'subdomain_level', ['TYPE'=>Table::TYPE_SMALLINT,'unsigned'=>false,'nullable' => false,'default'=>0, 'COMMENT'=>'subdomain level']);
        $connection->addColumn($vendorTable, 'update_store_base_url', ['TYPE'=>Table::TYPE_SMALLINT,'unsigned'=>false,'nullable' => false,'default'=>-1, 'COMMENT'=>'update_store_base_url']);

        $connection->addForeignKey(
            $installer->getFkName($adminTable, 'udropship_vendor', $vendorTable, 'vendor_id'),
            $adminTable, 'udropship_vendor', $vendorTable, 'vendor_id'
        );

        $tableName = $installer->getTable('udropship_vendor_registration');
        $table = $connection->newTable($tableName)
            ->addColumn('reg_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('store_id', Table::TYPE_SMALLINT, null, ['unsigned' => true,'nullable' => false])
            ->addColumn('vendor_name', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('telephone', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('email', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('password_enc', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('password_hash', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('carrier_code', Table::TYPE_TEXT, 64, ['nullable' => true])
            ->addColumn('vendor_attn', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('street', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable' => true])
            ->addColumn('city', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('zip', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('region_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => true])
            ->addColumn('region', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('country_id', Table::TYPE_TEXT, 2, ['nullable' => true])
            ->addColumn('remote_ip', Table::TYPE_TEXT, 15, ['nullable' => true])
            ->addColumn('registered_at', Table::TYPE_DATETIME, null, ['nullable' => true])
            ->addColumn('url_key', Table::TYPE_TEXT, 64, ['nullable' => true])
            ->addColumn('subdomain_level', Table::TYPE_SMALLINT, null, ['unsigned'=>false,'nullable'=>false,'default'=>0])
            ->addColumn('update_store_base_url', Table::TYPE_SMALLINT, null, ['unsigned'=>false,'nullable'=>false,'default'=>-1])
            ->addColumn('custom_vars_combined', Table::TYPE_TEXT, Table::MAX_TEXT_SIZE, ['nullable'=>false])
            ->addColumn('comments', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable'=>true])
            ->addColumn('notes', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable'=>true])
            ->setComment('Vendor Registrations')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $installer->endSetup();
    }
}