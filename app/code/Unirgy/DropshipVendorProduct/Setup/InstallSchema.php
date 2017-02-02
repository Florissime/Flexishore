<?php

namespace Unirgy\DropshipVendorProduct\Setup;

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

        $cfgTable = $installer->getTable('core_config_data');
        $connection->modifyColumn($cfgTable, 'value', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>self::MEDIUMTEXT_SIZE,'nullable' => false,'COMMENT'=>'Config Value']);

        $installer->endSetup();
    }
}