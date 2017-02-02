<?php
/**
 * Created by pp
 *
 * @project magento2
 */

namespace Unirgy\DropshipShippingClass\Setup;

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
        // table names
        $tableUdshipShipping = $installer->getTable('udropship_shipping');
        $tableUdshipclassCustomer = $installer->getTable('udshipclass_customer');
        $tableUdshipclassCustomerRow = $installer->getTable('udshipclass_customer_row');
        $tableUdshipclassVendor = $installer->getTable('udshipclass_vendor');
        $tableUdshipclassVendorRow = $installer->getTable('udshipclass_vendor_row');

        if ($connection->isTableExists($tableUdshipShipping)) {
            $connection->addColumn($tableUdshipShipping, 'vendor_ship_class',
                                   [
                                       'type' => Table::TYPE_TEXT,
                                       'length' => 255,
                                       'COMMENT'=>'vendor_ship_class'
                                   ]
            );
            $connection->addColumn($tableUdshipShipping, 'customer_ship_class',
                                   [
                                       'type' => Table::TYPE_TEXT,
                                       'length' => 255,
                                       'COMMENT'=>'customer_ship_class'
                                   ]
            );
        }

        if ($connection->isTableExists($tableUdshipclassCustomer) != true) {
            $table = $connection->newTable($tableUdshipclassCustomer)
                ->addColumn('class_id', Table::TYPE_SMALLINT, 6, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ], 'Class Id')
                ->addColumn('class_name', Table::TYPE_TEXT, 255, ['nullable' => false])
                ->addColumn('country_id', Table::TYPE_TEXT, 2, ['nullable' => false])
                ->addColumn('region_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
                ->addColumn('postcode', Table::TYPE_TEXT, null, ['nullable' => true])
                ->addColumn('sort_order', Table::TYPE_SMALLINT, 6, ['nullable' => false])
                ->setComment('DropshipClass Customers Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $connection->createTable($table);
        }

        if ($connection->isTableExists($tableUdshipclassVendor) != true) {
            $table = $connection->newTable($tableUdshipclassVendor)
                ->addColumn('class_id', Table::TYPE_SMALLINT, 6, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ], 'Class Id')
                ->addColumn('class_name', Table::TYPE_TEXT, 255, ['nullable' => false])
                ->addColumn('country_id', Table::TYPE_TEXT, 2, ['nullable' => false])
                ->addColumn('region_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
                ->addColumn('postcode', Table::TYPE_TEXT, null, ['nullable' => true])
                ->addColumn('sort_order', Table::TYPE_SMALLINT, 6, ['nullable' => false])
                ->setComment('DropshipClass Vendors Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $connection->createTable($table);
        }

        if ($connection->isTableExists($tableUdshipclassCustomerRow) != true) {
            $table = $connection->newTable($tableUdshipclassCustomerRow)
                ->addColumn('class_id', Table::TYPE_SMALLINT, 6, [
                    'unsigned' => true,
                    'nullable' => false,
                ])
                ->addColumn('country_id', Table::TYPE_TEXT, 2, ['nullable' => false])
                ->addColumn('region_id', Table::TYPE_TEXT, null, ['nullable' => false])
                ->addColumn('postcode', Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null])
                ->addIndex($installer->getIdxName($tableUdshipclassCustomerRow, ['class_id']), ['class_id'])
                ->addIndex($installer->getIdxName($tableUdshipclassCustomerRow, ['class_id', 'country_id']),
                           ['class_id', 'country_id'], ['type' => AdapterInterface::INDEX_TYPE_UNIQUE])
                ->addForeignKey(
                    $installer->getFkName($tableUdshipclassCustomerRow, 'class_id', $tableUdshipclassCustomer, 'class_id'),
                    'class_id', $tableUdshipclassCustomer, 'class_id', Table::ACTION_CASCADE
                )
                ->setComment('DropshipClass Customers Row Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $connection->createTable($table);
        }

        if ($connection->isTableExists($tableUdshipclassVendorRow) != true) {
            $table = $connection->newTable($tableUdshipclassVendorRow)
                ->addColumn('class_id', Table::TYPE_SMALLINT, 6, [
                    'unsigned' => true,
                    'nullable' => false,
                ])
                ->addColumn('country_id', Table::TYPE_TEXT, 2, ['nullable' => false])
                ->addColumn('region_id', Table::TYPE_TEXT, null, ['nullable' => false])
                ->addColumn('postcode', Table::TYPE_TEXT, null, ['nullable' => true, 'default' => null])
                ->addIndex($installer->getIdxName($tableUdshipclassVendorRow, ['class_id']), ['class_id'])
                ->addIndex($installer->getIdxName($tableUdshipclassVendorRow, ['class_id', 'country_id']),
                           ['class_id', 'country_id'], ['type' => AdapterInterface::INDEX_TYPE_UNIQUE])
                ->addForeignKey(
                    $installer->getFkName($tableUdshipclassVendorRow, 'class_id', $tableUdshipclassVendor, 'class_id'),
                    'class_id', $tableUdshipclassVendor, 'class_id', Table::ACTION_CASCADE
                )
                ->setComment('DropshipClass Customers Row Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $connection->createTable($table);
        }
        $installer->endSetup();
    }
}
