<?php

namespace Unirgy\DropshipVacation\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        // TODO: Implement install() method.
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        $vendorTable = $installer->getTable('udropship_vendor');
        $connection->addColumn($vendorTable, 'vacation_mode', ['TYPE' => Table::TYPE_SMALLINT, 'nullable' => true, 'COMMENT' => 'vacation_mode']);
        $connection->addColumn($vendorTable, 'vacation_end', ['TYPE' => Table::TYPE_DATETIME, 'nullable' => true, 'COMMENT' => 'vacation_end']);
        $connection->addColumn($vendorTable, 'vacation_message', ['TYPE' => Table::TYPE_TEXT, 'LENGTH' => 255, 'nullable' => true, 'COMMENT' => 'vacation_message']);

        $installer->endSetup();
    }
}