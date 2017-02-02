<?php

namespace Unirgy\DropshipTierShipping\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    protected $_moduleManager;

    public function __construct(\Magento\Framework\Module\Manager $moduleManager)
    {
        $this->_moduleManager = $moduleManager;
    }

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
        $connection->addColumn($vendorTable, 'tiership_use_v2_rates', ['TYPE'=>Table::TYPE_SMALLINT,'nullable' => true,'COMMENT'=>'tiership_use_v2_rates']);

        $dtTable = $installer->getTable('udtiership_delivery_type');
        $table = $connection->newTable($dtTable)
            ->addColumn('delivery_type_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('delivery_code', Table::TYPE_TEXT, 64, ['nullable' => true])
            ->addColumn('delivery_title', Table::TYPE_TEXT, 128, ['nullable' => true])
            ->addColumn('sort_order', Table::TYPE_SMALLINT, null, ['unsigned' => true,'nullable' => true])
            ->addIndex(
                $installer->getIdxName(
                    $dtTable,
                    ['delivery_code'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['delivery_code'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setComment('Tiership Delivery Type Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $srTable = $installer->getTable('udtiership_simple_rates');
        $vsrTable = $installer->getTable('udtiership_vendor_simple_rates');
        $scrTable = $installer->getTable('udtiership_simple_cond_rates');
        $vscrTable = $installer->getTable('udtiership_vendor_simple_cond_rates');
        $rTable = $installer->getTable('udtiership_rates');
        $vrTable = $installer->getTable('udtiership_vendor_rates');

        $tableNames = [$srTable, $vsrTable, $scrTable, $vscrTable, $rTable, $vrTable];
        $tables = [];

        foreach ($tableNames as $tableName) {
            $__table = $connection->newTable($tableName);
            $__table
                ->addColumn('rate_id', Table::TYPE_INTEGER, 10, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true
                ])
                ->addColumn('delivery_type_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false]);
            $unqColumns = ['delivery_type_id','customer_shipclass_id','customer_group_id'];
            if (false !== strpos($tableName, 'vendor')) {
                $__table->addColumn('vendor_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => true]);
                $__table->addIndex(
                    $installer->getIdxName($tableName, ['vendor_id'], AdapterInterface::INDEX_TYPE_INDEX),
                    ['vendor_id'],
                    ['type' => AdapterInterface::INDEX_TYPE_INDEX]
                );
                $__table->addForeignKey(
                    $installer->getFkName($tableName, 'vendor_id', $vendorTable, 'vendor_id'),
                    'vendor_id',
                    $vendorTable,
                    'vendor_id',
                    Table::ACTION_CASCADE
                );
                $unqColumns[] = 'vendor_id';
            }
            if (false !== strpos($tableName, 'cond')) {
                $__table->addColumn('condition_name', Table::TYPE_TEXT, 128, ['nullable' => true]);
                $__table->addColumn('condition', Table::TYPE_TEXT, Table::DEFAULT_TEXT_SIZE, ['nullable' => true]);
            } elseif (false !== strpos($tableName, 'simple')) {
                $__table->addColumn('cost', Table::TYPE_DECIMAL, [12,4], ['nullable' => false, 'default'=>0]);
                $__table->addColumn('additional', Table::TYPE_DECIMAL, [12,4], ['nullable' => false, 'default'=>0]);
            } else {
                $__table->addColumn('category_ids', Table::TYPE_TEXT, 255, ['nullable' => true]);
                $unqColumns[] = 'category_ids';
                if (false === strpos($tableName, 'vendor')) {
                    $__table->addColumn('vendor_shipclass_id', Table::TYPE_TEXT, 255, ['nullable' => true]);
                    $unqColumns[] = 'vendor_shipclass_id';
                }
                $__table->addColumn('cost', Table::TYPE_DECIMAL, [12,4], ['nullable' => false, 'default'=>0]);
                $__table->addColumn('cost_extra', Table::TYPE_TEXT, Table::DEFAULT_TEXT_SIZE, ['nullable' => true]);
                $__table->addColumn('max_cost', Table::TYPE_DECIMAL, [12,4], ['nullable' => false, 'default'=>0]);
                $__table->addColumn('additional', Table::TYPE_DECIMAL, [12,4], ['nullable' => false, 'default'=>0]);
                $__table->addColumn('additional_extra', Table::TYPE_TEXT, Table::DEFAULT_TEXT_SIZE, ['nullable' => true]);
                $__table->addColumn('max_additional', Table::TYPE_DECIMAL, [12,4], ['nullable' => false, 'default'=>0]);
                $__table->addColumn('handling', Table::TYPE_DECIMAL, [12,4], ['nullable' => false, 'default'=>0]);
                $__table->addColumn('handling_extra', Table::TYPE_TEXT, Table::DEFAULT_TEXT_SIZE, ['nullable' => true]);
                $__table->addColumn('max_handling', Table::TYPE_DECIMAL, [12,4], ['nullable' => false, 'default'=>0]);
            }
            $__table->addIndex(
                $installer->getIdxName($tableName, ['delivery_type_id'], AdapterInterface::INDEX_TYPE_INDEX),
                ['delivery_type_id'],
                ['type' => AdapterInterface::INDEX_TYPE_INDEX]
            );
            $__table->addForeignKey(
                $installer->getFkName($tableName, 'delivery_type_id', $dtTable, 'delivery_type_id'),
                'delivery_type_id',
                $dtTable,
                'delivery_type_id',
                Table::ACTION_CASCADE
            );
            $__table->addIndex(
                $installer->getIdxName(
                    $tableName,
                    $unqColumns,
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                $unqColumns,
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            );
            $__table
                ->addColumn('customer_shipclass_id', Table::TYPE_TEXT, 255, ['nullable' => false, 'default'=>'*'])
                ->addColumn('customer_group_id', Table::TYPE_TEXT, 255, ['nullable' => false, 'default'=>'*'])
                ->addColumn('sort_order', Table::TYPE_SMALLINT, null, ['unsigned' => true,'nullable' => true])
                ->setComment($tableName)
                ->setOption('type', 'InnoDB')
                ->setOption('collate', 'latin1_general_ci')
                ->setOption('charset', 'latin1');
            ;
            $tables[$tableName] = $__table;
        }
        foreach ($tables as $tableName=>$table) {
            $connection->createTable($table);
        }

        $productTable = $installer->getTable('catalog_product_entity');
        $prTable = $installer->getTable('udtiership_product_rates');
        $prUnqKey = ['product_id','delivery_type_id','customer_shipclass_id','customer_group_id'];
        $table = $connection->newTable($prTable)
            ->addColumn('rate_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('product_id', Table::TYPE_INTEGER, null, ['unsigned' => true,'nullable' => false])
            ->addColumn('delivery_type_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('customer_shipclass_id', Table::TYPE_TEXT, 255, ['nullable' => false, 'default'=>'*'])
            ->addColumn('customer_group_id', Table::TYPE_TEXT, 255, ['nullable' => false, 'default'=>'*'])
            ->addColumn('cost', Table::TYPE_DECIMAL, [12,4], ['nullable' => false, 'default'=>0])
            ->addColumn('additional', Table::TYPE_DECIMAL, [12,4], ['nullable' => false, 'default'=>0])
            ->addColumn('handling', Table::TYPE_DECIMAL, [12,4], ['nullable' => false, 'default'=>0])
            ->addColumn('sort_order', Table::TYPE_SMALLINT, null, ['unsigned' => true,'nullable' => true])
            ->addIndex(
                $installer->getIdxName(
                    $prTable,
                    ['delivery_type_id'],
                    AdapterInterface::INDEX_TYPE_INDEX
                ),
                ['delivery_type_id'],
                ['type' => AdapterInterface::INDEX_TYPE_INDEX]
            )
            ->addIndex(
                $installer->getIdxName(
                    $prTable,
                    ['product_id'],
                    AdapterInterface::INDEX_TYPE_INDEX
                ),
                ['product_id'],
                ['type' => AdapterInterface::INDEX_TYPE_INDEX]
            )
            ->addForeignKey(
                $installer->getFkName($prTable, 'delivery_type_id', $dtTable, 'delivery_type_id'),
                'delivery_type_id',
                $dtTable,
                'delivery_type_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName($prTable, 'product_id', $productTable, 'entity_id'),
                'product_id',
                $productTable,
                'entity_id',
                Table::ACTION_CASCADE
            )
            ->addIndex(
                $installer->getIdxName(
                    $prTable,
                    $prUnqKey,
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                $prUnqKey,
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setComment('Tiership Product Table')
            ->setOption('type', 'InnoDB')
            ->setOption('collate', 'latin1_general_ci')
            ->setOption('charset', 'latin1');
        $connection->createTable($table);

        $installer->endSetup();
    }
}