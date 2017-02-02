<?php

namespace Unirgy\DropshipTierCommission\Setup;

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
        $connection->addColumn($vendorTable, 'tiercom_rates', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>Table::DEFAULT_TEXT_SIZE,'nullable' => true,'COMMENT'=>'tiercom_rates']);
        $connection->addColumn($vendorTable, 'tiercom_fixed_rule', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'tiercom_fixed_rule']);
        $connection->addColumn($vendorTable, 'tiercom_fixed_rates', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>Table::DEFAULT_TEXT_SIZE,'nullable' => true,'COMMENT'=>'tiercom_fixed_rates']);
        $connection->addColumn($vendorTable, 'tiercom_fixed_calc_type', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'tiercom_fixed_calc_type']);

        $shipmentItemTable = $installer->getTable('sales_shipment_item');
        $connection->addColumn($shipmentItemTable, 'commission_percent', ['TYPE'=>Table::TYPE_DECIMAL,'LENGTH'=>'12,4','nullable' => true,'default'=>0,'COMMENT'=>'commission_percent']);
        $connection->addColumn($shipmentItemTable, 'transaction_fee', ['TYPE'=>Table::TYPE_DECIMAL,'LENGTH'=>'12,4','nullable' => true,'default'=>0,'COMMENT'=>'transaction_fee']);

        $statementRowTable = $installer->getTable('udropship_vendor_statement_row');
        $connection->dropIndex($statementRowTable, $installer->getIdxName(
            $statementRowTable,
            ['po_id','po_type','statement_id'],
            AdapterInterface::INDEX_TYPE_UNIQUE
        ));
        $connection->addColumn($statementRowTable, 'po_item_id', ['TYPE' => Table::TYPE_INTEGER, 'nullable' => true, 'unsigned' => true, 'COMMENT' => 'po_item_id']);
        $connection->addColumn($statementRowTable, 'sku', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'sku']);
        $connection->addColumn($statementRowTable, 'simple_sku', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'simple_sku']);
        $connection->addColumn($statementRowTable, 'vendor_sku', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'vendor_sku']);
        $connection->addColumn($statementRowTable, 'vendor_simple_sku', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'vendor_simple_sku']);
        $connection->addColumn($statementRowTable, 'product', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>255,'nullable' => true,'COMMENT'=>'product']);
        $connection->addColumn($statementRowTable, 'total_payment', ['TYPE' => Table::TYPE_DECIMAL, 'LENGTH' => '12,4', 'nullable' => true, 'default' => 0, 'COMMENT' => 'total_payment']);
        $connection->addColumn($statementRowTable, 'total_invoice', ['TYPE' => Table::TYPE_DECIMAL, 'LENGTH' => '12,4', 'nullable' => true, 'default' => 0, 'COMMENT' => 'total_invoice']);
        $connection->addIndex(
            $statementRowTable,
            $installer->getIdxName(
                $statementRowTable,
                ['po_id','po_type','statement_id','po_item_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['po_id','po_type','statement_id','po_item_id'],
            AdapterInterface::INDEX_TYPE_UNIQUE
        );

        $refundRowTable = $installer->getTable('udropship_vendor_statement_refund_row');
        $connection->dropIndex($refundRowTable, $installer->getIdxName(
            $refundRowTable,
            ['refund_id','po_id','po_type','statement_id'],
            AdapterInterface::INDEX_TYPE_UNIQUE
        ));
        $connection->addColumn($refundRowTable, 'po_item_id', ['TYPE' => Table::TYPE_INTEGER, 'nullable' => true, 'unsigned' => true, 'COMMENT' => 'po_item_id']);
        $connection->addColumn($refundRowTable, 'refund_item_id', ['TYPE' => Table::TYPE_INTEGER, 'nullable' => true, 'unsigned' => true, 'COMMENT' => 'refund_item_id']);
        $connection->addColumn($refundRowTable, 'sku', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'sku']);
        $connection->addColumn($refundRowTable, 'simple_sku', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'simple_sku']);
        $connection->addColumn($refundRowTable, 'vendor_sku', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'vendor_sku']);
        $connection->addColumn($refundRowTable, 'vendor_simple_sku', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>128,'nullable' => true,'COMMENT'=>'vendor_simple_sku']);
        $connection->addColumn($refundRowTable, 'product', ['TYPE'=>Table::TYPE_TEXT,'LENGTH'=>255,'nullable' => true,'COMMENT'=>'product']);
        $connection->addColumn($refundRowTable, 'total_payment', ['TYPE' => Table::TYPE_DECIMAL, 'LENGTH' => '12,4', 'nullable' => true, 'default' => 0, 'COMMENT' => 'total_payment']);
        $connection->addColumn($refundRowTable, 'total_invoice', ['TYPE' => Table::TYPE_DECIMAL, 'LENGTH' => '12,4', 'nullable' => true, 'default' => 0, 'COMMENT' => 'total_invoice']);
        $connection->addIndex(
            $refundRowTable,
            $installer->getIdxName(
                $refundRowTable,
                ['refund_id','po_id','po_type','statement_id','po_item_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['refund_id','po_id','po_type','statement_id','po_item_id'],
            AdapterInterface::INDEX_TYPE_UNIQUE
        );

        if ((bool)$this->_moduleManager->isEnabled('Unirgy_DropshipPo')) {
            $udpoItemTable = $installer->getTable('udropship_po_item');
            $connection->addColumn($udpoItemTable, 'commission_percent', ['TYPE' => Table::TYPE_DECIMAL, 'LENGTH' => '12,4', 'nullable' => true, 'default' => 0, 'COMMENT' => 'commission_percent']);
            $connection->addColumn($udpoItemTable, 'transaction_fee', ['TYPE' => Table::TYPE_DECIMAL, 'LENGTH' => '12,4', 'nullable' => true, 'default' => 0, 'COMMENT' => 'transaction_fee']);
        }

        if ((bool)$this->_moduleManager->isEnabled('Unirgy_DropshipPayout')) {
            $payoutRowTable = $installer->getTable('udropship_payout_row');
            $connection->dropIndex($payoutRowTable, $installer->getIdxName(
                $payoutRowTable,
                ['po_id','po_type','payout_id'],
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
    }
}