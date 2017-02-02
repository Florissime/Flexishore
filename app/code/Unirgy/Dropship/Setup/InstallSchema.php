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

namespace Unirgy\Dropship\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    const MEDIUMTEXT_SIZE=16777216;
    const TEXT_SIZE=65536;
    protected $_hlp;
    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper
    ) {
        $this->_hlp = $udropshipHelper;
    }
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        $tableName = $installer->getTable('udropship_shipping');
        $table = $connection->newTable($tableName)
            ->addColumn('shipping_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('shipping_code', Table::TYPE_TEXT, 30, ['nullable' => false])
            ->addColumn('shipping_title', Table::TYPE_TEXT, 100, ['nullable' => false])
            ->addColumn('days_in_transit', Table::TYPE_TEXT, 20, ['nullable' => false])
            ->setComment('Shipping Methods Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_shipping_title');
        $table = $connection->newTable($tableName)
            ->addColumn('title_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('shipping_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('store_id', Table::TYPE_SMALLINT, null, ['unsigned' => true,'nullable' => false])
            ->addColumn('title', Table::TYPE_TEXT, 255, ['nullable' => false, 'default' => ''])
            ->addIndex(
                $installer->getIdxName(
                    $tableName,
                    ['shipping_id', 'store_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['shipping_id', 'store_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['store_id']),
                ['store_id']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['shipping_id']),
                ['shipping_id']
            )
            ->addForeignKey(
                $installer->getFkName($tableName, 'shipping_id', 'udropship_shipping', 'shipping_id'),
                'shipping_id',
                $installer->getTable('udropship_shipping'),
                'shipping_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName($tableName, 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Associated System Methods Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_shipping_method');
        $table = $connection->newTable($tableName)
            ->addColumn('shipping_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('carrier_code', Table::TYPE_TEXT, 100, ['nullable' => false, 'default' => ''])
            ->addColumn('method_code', Table::TYPE_TEXT, 255, ['nullable' => false, 'default' => ''])
            ->addForeignKey(
                $installer->getFkName($tableName, 'shipping_id', 'udropship_shipping', 'shipping_id'),
                'shipping_id',
                $installer->getTable('udropship_shipping'),
                'shipping_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Associated System Methods Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_shipping_website');
        $table = $connection->newTable($tableName)
            ->addColumn('shipping_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('website_id', Table::TYPE_SMALLINT, null, ['unsigned' => true,'nullable' => false])
            ->addIndex(
                $installer->getIdxName(
                    $tableName,
                    ['shipping_id', 'website_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['shipping_id', 'website_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['website_id']),
                ['website_id']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['shipping_id']),
                ['shipping_id']
            )
            ->addForeignKey(
                $installer->getFkName($tableName, 'shipping_id', 'udropship_shipping', 'shipping_id'),
                'shipping_id',
                $installer->getTable('udropship_shipping'),
                'shipping_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName($tableName, 'website_id', 'store_website', 'website_id'),
                'website_id',
                $installer->getTable('store_website'),
                'website_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Shipping Website Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_vendor');
        $table = $connection->newTable($tableName)
            ->addColumn('vendor_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('vendor_name', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('vendor_attn', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('email', Table::TYPE_TEXT, 127, ['nullable' => false])
            ->addColumn('telephone', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('fax', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('street', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('city', Table::TYPE_TEXT, 50, ['nullable' => false])
            ->addColumn('zip', Table::TYPE_TEXT, 20, ['nullable' => true])
            ->addColumn('country_id', Table::TYPE_TEXT, 2, ['nullable' => false])
            ->addColumn('region_id', Table::TYPE_INTEGER, 8, ['nullable' => true])
            ->addColumn('region', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('billing_use_shipping', Table::TYPE_SMALLINT, null, ['nullable' => false,'default' => 1])
            ->addColumn('billing_vendor_attn', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('billing_email', Table::TYPE_TEXT, 127, ['nullable' => false])
            ->addColumn('billing_telephone', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('billing_fax', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('billing_street', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('billing_city', Table::TYPE_TEXT, 50, ['nullable' => false])
            ->addColumn('billing_zip', Table::TYPE_TEXT, 20, ['nullable' => true])
            ->addColumn('billing_country_id', Table::TYPE_TEXT, 2, ['nullable' => false])
            ->addColumn('billing_region_id', Table::TYPE_INTEGER, 8, ['nullable' => true])
            ->addColumn('billing_region', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('status', Table::TYPE_TEXT, 1, ['nullable' => false])
            ->addColumn('password', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('password_hash', Table::TYPE_TEXT, 100, ['nullable' => true])
            ->addColumn('password_enc', Table::TYPE_TEXT, 100, ['nullable' => true])
            ->addColumn('carrier_code', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('notify_new_order', Table::TYPE_SMALLINT, null, ['nullable' => false,'default' => 1])
            ->addColumn('label_type', Table::TYPE_TEXT, 10, ['nullable' => false, 'default' => 'PDF'])
            ->addColumn('test_mode', Table::TYPE_SMALLINT, null, ['nullable' => false,'default' => 0])
            ->addColumn('handling_fee', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('ups_shipper_number', Table::TYPE_TEXT, 10, ['nullable' => false])
            ->addColumn('custom_data_combined', Table::TYPE_TEXT, Table::MAX_TEXT_SIZE, ['nullable' => false])
            ->addColumn('custom_vars_combined', Table::TYPE_TEXT, Table::MAX_TEXT_SIZE, ['nullable' => false])
            ->addColumn('email_template', Table::TYPE_INTEGER, 7, ['unsigned' => true,'nullable' => false])
            ->addColumn('url_key', Table::TYPE_TEXT, 64, ['nullable' => true])
            ->addColumn('random_hash', Table::TYPE_TEXT, 64, ['nullable' => true])
            ->addColumn('created_at', Table::TYPE_TIMESTAMP, null, [])
            ->addColumn('use_handling_fee', Table::TYPE_SMALLINT, null, ['nullable' => false,'default' => 0])
            ->addColumn('allow_shipping_extra_charge', Table::TYPE_SMALLINT, null, ['nullable' => false,'default' => 0])
            ->addColumn('default_shipping_extra_charge_suffix', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('default_shipping_extra_charge_type', Table::TYPE_TEXT, 32, ['nullable' => false])
            ->addColumn('default_shipping_extra_charge', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('is_extra_charge_shipping_default', Table::TYPE_SMALLINT, null, ['nullable' => false,'default' => 0])
            ->addColumn('default_shipping_id', Table::TYPE_INTEGER, 10, ['nullable' => true])
            ->addColumn('use_rates_fallback', Table::TYPE_SMALLINT, null, ['nullable' => false,'default' => 0])
            ->addColumn('notify_lowstock', Table::TYPE_SMALLINT, null, ['nullable' => false,'default' => 0])
            ->addColumn('notify_lowstock_qty', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addIndex(
                $installer->getIdxName(
                    $tableName,
                    ['url_key'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['url_key'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['vendor_name']),
                ['vendor_name']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['status']),
                ['status']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['notify_lowstock']),
                ['notify_lowstock']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['random_hash']),
                ['random_hash']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['created_at']),
                ['created_at']
            )
            ->setComment('Vendors Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_vendor_product');
        $table = $connection->newTable($tableName)
            ->addColumn('vendor_product_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('vendor_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('product_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('priority', Table::TYPE_SMALLINT, null, ['unsigned' => true,'nullable' => true])
            ->addColumn('carrier_code', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('vendor_sku', Table::TYPE_TEXT, 64, ['nullable' => true])
            ->addColumn('vendor_cost', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('stock_qty', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addIndex(
                $installer->getIdxName(
                    'udropship_vendor_product',
                    ['vendor_id', 'product_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['vendor_id', 'product_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['vendor_id']),
                ['vendor_id']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['product_id']),
                ['product_id']
            )
            ->addForeignKey(
                $installer->getFkName($tableName, 'vendor_id', 'udropship_vendor', 'vendor_id'),
                'vendor_id',
                $installer->getTable('udropship_vendor'),
                'vendor_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName($tableName, 'product_id', 'catalog_product_entity', 'entity_id'),
                'product_id',
                $installer->getTable('catalog_product_entity'),
                'entity_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Vendor Products Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_vendor_product_assoc');
        $table = $connection->newTable($tableName)
            ->addColumn('vendor_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('product_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('is_attribute', Table::TYPE_SMALLINT, null, ['nullable' => false])
            ->addColumn('is_udmulti', Table::TYPE_SMALLINT, null, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName(
                    'udropship_vendor_product',
                    ['vendor_id', 'product_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['vendor_id', 'product_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['vendor_id']),
                ['vendor_id']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['product_id']),
                ['product_id']
            )
            ->addForeignKey(
                $installer->getFkName($tableName, 'vendor_id', 'udropship_vendor', 'vendor_id'),
                'vendor_id',
                $installer->getTable('udropship_vendor'),
                'vendor_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName($tableName, 'product_id', 'catalog_product_entity', 'entity_id'),
                'product_id',
                $installer->getTable('catalog_product_entity'),
                'entity_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Vendor-Product Assoc Index Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_vendor_shipping');
        $table = $connection->newTable($tableName)
            ->addColumn('vendor_shipping_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('vendor_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('shipping_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('account_id', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('price_type', Table::TYPE_SMALLINT, null, ['nullable' => true])
            ->addColumn('price', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('priority', Table::TYPE_SMALLINT, null, ['unsigned' => true,'nullable' => false])
            ->addColumn('handling_fee', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('carrier_code', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('est_carrier_code', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('allow_extra_charge', Table::TYPE_SMALLINT, null, ['unsigned' => true,'nullable' => false])
            ->addColumn('extra_charge_suffix', Table::TYPE_TEXT, 255, ['nullable' => true])
            ->addColumn('extra_charge_type', Table::TYPE_TEXT, 32, ['nullable' => true])
            ->addColumn('extra_charge', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addIndex(
                $installer->getIdxName(
                    $tableName,
                    ['vendor_id', 'shipping_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['vendor_id', 'shipping_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['vendor_id']),
                ['vendor_id']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['shipping_id']),
                ['shipping_id']
            )
            ->addForeignKey(
                $installer->getFkName($tableName, 'vendor_id', 'udropship_vendor', 'vendor_id'),
                'vendor_id',
                $installer->getTable('udropship_vendor'),
                'vendor_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName($tableName, 'shipping_id', 'udropship_shipping', 'shipping_id'),
                'shipping_id',
                $installer->getTable('udropship_shipping'),
                'shipping_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Vendor Shipping Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_label_batch');
        $table = $connection->newTable($tableName)
            ->addColumn('batch_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('title', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('label_type', Table::TYPE_TEXT, 10, ['nullable' => false, 'default'=>'PDF'])
            ->addColumn('created_at', Table::TYPE_DATETIME, null, ['nullable' => true])
            ->addColumn('vendor_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('username', Table::TYPE_TEXT, 50, ['nullable' => false])
            ->addColumn('shipment_cnt', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addIndex(
                $installer->getIdxName($tableName, ['vendor_id']),
                ['vendor_id']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['created_at']),
                ['created_at']
            )
            ->setComment('Shipping Labels Batch Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_label_shipment');
        $table = $connection->newTable($tableName)
            ->addColumn('batch_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('order_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('shipment_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addIndex(
                $installer->getIdxName(
                    $tableName,
                    ['batch_id', 'order_id', 'shipment_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['batch_id', 'order_id', 'shipment_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['order_id']),
                ['order_id']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['shipment_id']),
                ['shipment_id']
            )
            ->setComment('Shipping Labels Batch Link Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_vendor_statement');
        $table = $connection->newTable($tableName)
            ->addColumn('vendor_statement_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('vendor_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('statement_id', Table::TYPE_TEXT, 30, ['nullable' => false])
            ->addColumn('statement_filename', Table::TYPE_TEXT, 128, ['nullable' => false])
            ->addColumn('statement_period', Table::TYPE_TEXT, 30, ['nullable' => false])
            ->addColumn('order_date_from', Table::TYPE_DATETIME, null, ['nullable' => false])
            ->addColumn('order_date_to', Table::TYPE_DATETIME, null, ['nullable' => false])
            ->addColumn('total_orders', Table::TYPE_INTEGER, null, ['nullable' => true])
            ->addColumn('total_payout', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('total_paid', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('total_due', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('total_payment', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('total_invoice', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('total_adjustment', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('total_refund', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('payment_due', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('invoice_due', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('payment_paid', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('invoice_paid', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('subtotal', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('shipping', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('tax', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('hidden_tax', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('handling', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('trans_fee', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('com_amount', Table::TYPE_DECIMAL, [12,4], ['nullable' => true])
            ->addColumn('created_at', Table::TYPE_DATETIME, null, ['nullable' => false])
            ->addColumn('po_type', Table::TYPE_TEXT, 32, ['nullable' => false, 'default'=>'shipment'])
            ->addColumn('orders_data', Table::TYPE_TEXT, Table::MAX_TEXT_SIZE, ['nullable' => false])
            ->addColumn('notes', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable' => false])
            ->addColumn('use_locale_timezone', Table::TYPE_SMALLINT, null, ['nullable' => false])
            ->addColumn('email_sent', Table::TYPE_SMALLINT, null, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName(
                    $tableName,
                    ['statement_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['statement_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['vendor_id']),
                ['vendor_id']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['statement_period']),
                ['statement_period']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['email_sent']),
                ['email_sent']
            )
            ->addForeignKey(
                $installer->getFkName($tableName, 'vendor_id', 'udropship_vendor', 'vendor_id'),
                'vendor_id',
                $installer->getTable('udropship_vendor'),
                'vendor_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Vendor Statement Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_vendor_statement_row');
        $table = $connection->newTable($tableName)
            ->addColumn('row_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('statement_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
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
            ->addColumn('has_error', Table::TYPE_SMALLINT, null, ['nullable' => false])
            ->addColumn('error_info', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable' => false])
            ->addColumn('row_json', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable' => false])
            ->addColumn('paid', Table::TYPE_SMALLINT, null, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName(
                    $tableName,
                    ['po_id','po_type','statement_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['po_id','po_type','statement_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['statement_id']),
                ['statement_id']
            )
            ->addForeignKey(
                $installer->getFkName($tableName, 'statement_id', 'udropship_vendor_statement', 'vendor_statement_id'),
                'statement_id',
                $installer->getTable('udropship_vendor_statement'),
                'vendor_statement_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Vendor Statement Row Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_vendor_statement_refund_row');
        $table = $connection->newTable($tableName)
            ->addColumn('row_id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('statement_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('refund_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('order_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('po_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('po_type', Table::TYPE_TEXT, 32, ['nullable' => false, 'default'=>'shipment'])
            ->addColumn('refund_increment_id', Table::TYPE_TEXT, 50, ['nullable' => false])
            ->addColumn('order_increment_id', Table::TYPE_TEXT, 50, ['nullable' => false])
            ->addColumn('po_increment_id', Table::TYPE_TEXT, 50, ['nullable' => false])
            ->addColumn('refund_created_at', Table::TYPE_DATETIME, null, ['nullable' => false])
            ->addColumn('order_created_at', Table::TYPE_DATETIME, null, ['nullable' => false])
            ->addColumn('po_created_at', Table::TYPE_DATETIME, null, ['nullable' => false])
            ->addColumn('total_refund', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('subtotal', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('shipping', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('discount', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('tax', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('hidden_tax', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('handling', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('trans_fee', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('com_amount', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('adj_amount', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('has_error', Table::TYPE_SMALLINT, null, ['nullable' => false])
            ->addColumn('error_info', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable' => false])
            ->addColumn('row_json', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName(
                    $tableName,
                    ['refund_id','po_id','po_type','statement_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['refund_id','po_id','po_type','statement_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['statement_id']),
                ['statement_id']
            )
            ->addForeignKey(
                $installer->getFkName($tableName, 'statement_id', 'udropship_vendor_statement', 'vendor_statement_id'),
                'statement_id',
                $installer->getTable('udropship_vendor_statement'),
                'vendor_statement_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Vendor Statement Refund Row Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_vendor_statement_adjustment');
        $table = $connection->newTable($tableName)
            ->addColumn('id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('adjustment_prefix', Table::TYPE_TEXT, 64, ['nullable' => true])
            ->addColumn('adjustment_id', Table::TYPE_TEXT, 64, ['nullable' => true])
            ->addColumn('statement_id', Table::TYPE_TEXT, 30, ['nullable' => true])
            ->addColumn('po_id', Table::TYPE_TEXT, 50, ['nullable' => false, 'default'=>''])
            ->addColumn('po_type', Table::TYPE_TEXT, 32, ['nullable' => false, 'default'=>'shipment'])
            ->addColumn('created_at', Table::TYPE_DATETIME, null, ['nullable' => true])
            ->addColumn('username', Table::TYPE_TEXT, 50, ['nullable' => true])
            ->addColumn('amount', Table::TYPE_DECIMAL, [12,4], ['nullable' => false])
            ->addColumn('comment', Table::TYPE_TEXT, self::TEXT_SIZE, ['nullable' => false])
            ->addColumn('paid', Table::TYPE_SMALLINT, null, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName(
                    $tableName,
                    ['adjustment_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['adjustment_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['statement_id']),
                ['statement_id']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['po_id','po_type']),
                ['po_id','po_type']
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['created_at']),
                ['created_at']
            )
            ->setComment('Vendor Statement Adjustment Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $tableName = $installer->getTable('udropship_vendor_lowstock');
        $table = $connection->newTable($tableName)
            ->addColumn('id', Table::TYPE_INTEGER, 10, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ])
            ->addColumn('vendor_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => false])
            ->addColumn('product_id', Table::TYPE_INTEGER, 10, ['unsigned' => true,'nullable' => true])
            ->addColumn('notified_at', Table::TYPE_DATETIME, null, ['nullable' => true])
            ->addColumn('notified', Table::TYPE_SMALLINT, null, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName(
                    $tableName,
                    ['vendor_id','product_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['vendor_id','product_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName($tableName, ['product_id']),
                ['product_id']
            )
            ->setComment('Vendor Lowstock Table')
            ->setOption('type', 'InnoDB')
            ->setOption('charset', 'utf8');
        $connection->createTable($table);

        $installer->endSetup();
    }
}