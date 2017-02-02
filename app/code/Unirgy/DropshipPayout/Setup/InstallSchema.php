<?php

namespace Unirgy\DropshipPayout\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    const LONGTEXT_SIZE=4294967295;
    const MEDIUMTEXT_SIZE=16777216;
    const TEXT_SIZE=65536;

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

        $shipmentTable = $installer->getTable('sales_shipment');
        $connection->addColumn($shipmentTable, 'payout_id', ['TYPE' => Table::TYPE_INTEGER, 'unsigned'=>true, 'nullable' => true, 'COMMENT' => 'payout_id']);

        $shipmentGridTable = $installer->getTable('sales_shipment_grid');
        $connection->addColumn($shipmentGridTable, 'payout_id', ['TYPE' => Table::TYPE_INTEGER, 'unsigned'=>true, 'nullable' => true, 'COMMENT' => 'payout_id']);
        $connection->addIndex(
            $setup->getTable($shipmentGridTable),
            $connection->getIndexName(
                $setup->getTable($shipmentGridTable),
                'payout_id',
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
            ),
            'payout_id'
        );

        if ((bool)$this->_moduleManager->isEnabled('Unirgy_DropshipPo')) {
            $udpoTable = $installer->getTable('udropship_po');
            $connection->addColumn($udpoTable, 'payout_id', ['TYPE' => Table::TYPE_INTEGER, 'unsigned'=>true, 'nullable' => true, 'COMMENT' => 'payout_id']);

            $udpoGridTable = $installer->getTable('udropship_po_grid');
            $connection->addColumn($udpoGridTable, 'payout_id', ['TYPE' => Table::TYPE_INTEGER, 'unsigned'=>true, 'nullable' => true, 'COMMENT' => 'payout_id']);
            $connection->addIndex(
                $setup->getTable($udpoGridTable),
                $connection->getIndexName(
                    $setup->getTable($udpoGridTable),
                    'payout_id',
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
                ),
                'payout_id'
            );
        }

        $vendorTable = $installer->getTable('udropship_vendor');
        $connection->addColumn($vendorTable, 'payout_type', ['TYPE' => Table::TYPE_TEXT, 'LENGTH' => 50, 'nullable' => true, 'COMMENT' => 'payout_type']);
        $connection->addColumn($vendorTable, 'payout_method', ['TYPE' => Table::TYPE_TEXT, 'LENGTH' => 50, 'nullable' => true, 'COMMENT' => 'payout_method']);
        $connection->addColumn($vendorTable, 'payout_schedule', ['TYPE' => Table::TYPE_TEXT, 'LENGTH' => 50, 'nullable' => true, 'COMMENT' => 'payout_schedule']);
        $connection->addColumn($vendorTable, 'payout_schedule_type', ['TYPE' => Table::TYPE_TEXT, 'LENGTH' => 128, 'nullable' => true, 'COMMENT' => 'payout_schedule_type']);

        $payoutTable = $installer->getTable('udropship_payout');
        $table = $connection->newTable($payoutTable)
            ->addColumn('payout_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('vendor_id', Table::TYPE_INTEGER, null, ['nullable' => false, 'unsigned'=>true])
            ->addColumn('payout_type', Table::TYPE_TEXT, 50, ['nullable' => false])
            ->addColumn('payout_method', Table::TYPE_TEXT, 50, ['nullable' => false])
            ->addColumn('payout_status', Table::TYPE_TEXT, 20, ['nullable' => true])
            ->addColumn('po_type', Table::TYPE_TEXT, 32, ['nullable' => false, 'default'=>'shipment'])
            ->addColumn('is_online', Table::TYPE_SMALLINT, null, ['nullable' => true])
            ->addColumn('statement_id', Table::TYPE_TEXT, 30, ['nullable' => true])
            ->addColumn('paypal_unique_id', Table::TYPE_TEXT, 30, ['nullable' => true])
            ->addColumn('transaction_id', Table::TYPE_TEXT, 64, ['nullable' => true])
            ->addColumn('paypal_correlation_id', Table::TYPE_TEXT, 64, ['nullable' => true])
            ->addColumn('sender_transaction_id', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('transaction_status', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('sender_transaction_status', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('orders_data', Table::TYPE_TEXT, self::LONGTEXT_SIZE, ['nullable' => false])
            ->addColumn('total_orders', Table::TYPE_TEXT, self::MEDIUMTEXT_SIZE, ['nullable' => false])
            ->addColumn('transaction_fee', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('total_payout', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('total_paid', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('total_due', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('total_payment', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('total_invoice', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('payment_due', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('invoice_due', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('payment_paid', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('invoice_paid', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('subtotal', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('shipping', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('discount', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('tax', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('hidden_tax', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('handling', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('trans_fee', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('com_amount', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('adjustment', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('my_adjustment', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('total_adjustment', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('date_from', Table::TYPE_DATETIME, null, ['nullable' => true])
            ->addColumn('date_to', Table::TYPE_DATETIME, null, ['nullable' => true])
            ->addColumn('created_at', Table::TYPE_DATETIME, null, ['nullable' => true])
            ->addColumn('scheduled_at', Table::TYPE_DATETIME, null, ['nullable' => true])
            ->addColumn('updated_at', Table::TYPE_DATETIME, null, ['nullable' => true])
            ->addColumn('before_hold_status', Table::TYPE_TEXT, 20, ['nullable' => true])
            ->addColumn('notes', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable' => true])
            ->addColumn('error_info', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable' => true])
            ->addIndex(
                $installer->getIdxName(
                    $payoutTable,
                    ['payout_status'],
                    AdapterInterface::INDEX_TYPE_INDEX
                ),
                ['payout_status']
            )
            ->addIndex(
                $installer->getIdxName(
                    $payoutTable,
                    ['statement_id'],
                    AdapterInterface::INDEX_TYPE_INDEX
                ),
                ['statement_id']
            )
            ->addIndex(
                $installer->getIdxName(
                    $payoutTable,
                    ['created_at'],
                    AdapterInterface::INDEX_TYPE_INDEX
                ),
                ['created_at']
            )
            ->addIndex(
                $installer->getIdxName(
                    $payoutTable,
                    ['scheduled_at'],
                    AdapterInterface::INDEX_TYPE_INDEX
                ),
                ['scheduled_at']
            )
            ->setComment('Vendor Payout Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $payoutRowTable = $installer->getTable('udropship_payout_row');
        $table = $connection->newTable($payoutRowTable)
            ->addColumn('row_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('payout_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('order_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('po_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('po_type', Table::TYPE_TEXT, 32, ['nullable' => false, 'default'=>'shipment'])
            ->addColumn('order_increment_id', Table::TYPE_TEXT, 50, ['nullable' => false])
            ->addColumn('po_increment_id', Table::TYPE_TEXT, 50, ['nullable' => false])
            ->addColumn('order_created_at', Table::TYPE_DATETIME, null, ['nullable' => false])
            ->addColumn('po_created_at', Table::TYPE_DATETIME, null, ['nullable' => false])
            ->addColumn('po_statement_date', Table::TYPE_DATETIME, null, ['nullable' => false])
            ->addColumn('total_payout', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('subtotal', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('shipping', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('discount', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('tax', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('hidden_tax', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('handling', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('trans_fee', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('com_amount', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('adj_amount', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('use_locale_timezone', Table::TYPE_SMALLINT, null, ['nullable' => false])
            ->addColumn('has_error', Table::TYPE_SMALLINT, null, ['nullable' => false])
            ->addColumn('error_info', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable' => false])
            ->addColumn('row_json', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName(
                    $payoutRowTable,
                    ['po_id','po_type','payout_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['po_id','po_type','payout_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($payoutRowTable, ['payout_id']),
                ['payout_id']
            )
            ->addForeignKey(
                $installer->getFkName($payoutRowTable, 'payout_id', $payoutTable, 'payout_id'),
                'payout_id',
                $payoutTable,
                'payout_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Vendor Payout Row Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $payoutAdjustmentTable = $installer->getTable('udropship_payout_adjustment');
        $table = $connection->newTable($payoutAdjustmentTable)
            ->addColumn('id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('adjustment_prefix', Table::TYPE_TEXT, 64, ['nullable' => true])
            ->addColumn('adjustment_id', Table::TYPE_TEXT, 64, ['nullable' => true])
            ->addColumn('payout_id', Table::TYPE_TEXT, 30, ['nullable' => true])
            ->addColumn('po_id', Table::TYPE_TEXT, 50, ['nullable' => false, 'default'=>''])
            ->addColumn('po_type', Table::TYPE_TEXT, 32, ['nullable' => false, 'default'=>'shipment'])
            ->addColumn('created_at', Table::TYPE_DATETIME, null, ['nullable' => true])
            ->addColumn('username', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('amount', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('comment', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable' => false])
            ->addColumn('paid', Table::TYPE_SMALLINT, null, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName(
                    $payoutAdjustmentTable,
                    ['adjustment_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['adjustment_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($payoutAdjustmentTable, ['payout_id']),
                ['payout_id']
            )
            ->addIndex(
                $installer->getIdxName($payoutAdjustmentTable, ['po_id','po_type']),
                ['po_id','po_type']
            )
            ->addIndex(
                $installer->getIdxName($payoutAdjustmentTable, ['created_at']),
                ['created_at']
            )
            ->setComment('Vendor Payout Adjustment Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        if ((bool)$this->_moduleManager->isEnabled('Unirgy_DropshipTierCommission')) {
            $payoutRowTable = $installer->getTable('udropship_payout_row');
            $connection->dropIndex($payoutRowTable, $installer->getIdxName(
                $payoutRowTable,
                ['po_id','po_type','statement_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ));
            $connection->addColumn($payoutRowTable, 'po_item_id', ['TYPE' => Table::TYPE_INTEGER, 'nullable' => true, 'unsigned' => true, 'COMMENT' => 'po_item_id']);
            $connection->addColumn($payoutRowTable, 'sku', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'sku']);
            $connection->addColumn($payoutRowTable, 'simple_sku', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'simple_sku']);
            $connection->addColumn($payoutRowTable, 'vendor_sku', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'vendor_sku']);
            $connection->addColumn($payoutRowTable, 'vendor_simple_sku', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'vendor_simple_sku']);
            $connection->addColumn($payoutRowTable, 'product', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>255,'nullable' => true,'COMMENT'=>'product']);
            $connection->addColumn($payoutRowTable, 'total_payment', ['TYPE' => Table::TYPE_DECIMAL, 'LENGTH' => '12,4', 'nullable' => true, 'default' => 0, 'COMMENT' => 'total_payment']);
            $connection->addColumn($payoutRowTable, 'total_invoice', ['TYPE' => Table::TYPE_DECIMAL, 'LENGTH' => '12,4', 'nullable' => true, 'default' => 0, 'COMMENT' => 'total_invoice']);
            $connection->addIndex(
                $payoutRowTable,
                $installer->getIdxName(
                    $payoutRowTable,
                    ['po_id','po_type','payout_id','po_item_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['po_id','po_type','payout_id','po_item_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            );
        }

        $installer->endSetup();
    }
}