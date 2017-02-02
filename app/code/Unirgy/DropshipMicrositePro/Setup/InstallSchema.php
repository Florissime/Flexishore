<?php

namespace Unirgy\DropshipMicrositePro\Setup;

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

        $vendorTable = $installer->getTable('udropship_vendor');
        $connection->addColumn($vendorTable, 'confirmation', ['TYPE' => Table::TYPE_TEXT, 'LENGTH'=>64,  'nullable' => true, 'default'=>0, 'COMMENT'=>'confirmation_sent']);
        $connection->addColumn($vendorTable, 'confirmation_sent', ['TYPE' => Table::TYPE_SMALLINT, 'nullable' => false, 'default'=>0, 'COMMENT'=>'confirmation_sent']);
        $connection->addColumn($vendorTable, 'reject_reason', ['TYPE' => Table::TYPE_TEXT, 'COMMENT'=>'reject_reason']);

        $installer->endSetup();
    }
}