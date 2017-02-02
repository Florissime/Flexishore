<?php

namespace Unirgy\DropshipVendorRatings\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    const MEDIUMTEXT_SIZE = 16777216;
    const TEXT_SIZE = 65536;

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
        $connection->addColumn($vendorTable, 'allow_udratings', ['TYPE' => Table::TYPE_SMALLINT, 'nullable' => true, 'COMMENT' => 'allow_udratings']);

        $shipmentTable = $installer->getTable('sales_shipment');
        $connection->addColumn($shipmentTable, 'udrating_emails_sent', ['TYPE' => Table::TYPE_SMALLINT, 'unsigned'=>true, 'nullable' => false, 'COMMENT' => 'udrating_emails_sent', 'default'=>0]);
        $connection->addColumn($shipmentTable, 'udrating_date', ['TYPE' => Table::TYPE_DATETIME, 'nullable' => true, 'COMMENT' => 'udrating_date']);

        $shipmentGridTable = $installer->getTable('sales_shipment_grid');
        $connection->addColumn($shipmentGridTable, 'udrating_date', ['TYPE' => Table::TYPE_DATETIME, 'nullable' => true, 'COMMENT' => 'udrating_date']);
        $connection->addIndex(
            $setup->getTable($shipmentGridTable),
            $connection->getIndexName(
                $setup->getTable($shipmentGridTable),
                'udrating_date',
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
            ),
            'udrating_date'
        );

        $reviewTable = $installer->getTable('review');
        $connection->addColumn($reviewTable, 'rel_entity_pk_value', ['TYPE'=>Table::TYPE_INTEGER,'nullable' => true,'COMMENT'=>'rel_entity_pk_value']);
        $connection->addColumn($reviewTable, 'helpfulness_yes', ['TYPE'=>Table::TYPE_INTEGER,'nullable' => true,'COMMENT'=>'helpfulness_yes', 'default'=>0]);
        $connection->addColumn($reviewTable, 'helpfulness_no', ['TYPE'=>Table::TYPE_INTEGER,'nullable' => true,'COMMENT'=>'helpfulness_no', 'default'=>0]);
        $connection->addColumn($reviewTable, 'helpfulness_pcnt', ['TYPE'=>Table::TYPE_DECIMAL,'LENGTH'=>'10,2','nullable' => true,'COMMENT'=>'helpfulness_pcnt']);
        $connection->addIndex(
            $setup->getTable($reviewTable),
            $connection->getIndexName(
                $setup->getTable($reviewTable),
                'rel_entity_pk_value',
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
            ),
            'rel_entity_pk_value'
        );
        $ratingTable = $installer->getTable('rating');
        $connection->addColumn($ratingTable, 'is_aggregate', ['TYPE'=>Table::TYPE_SMALLINT,'nullable' => false,'COMMENT'=>'is_aggregate', 'DEFAULT'=>1]);
        $connection->addColumn($ratingTable, 'rating_code', ['TYPE'=>Table::TYPE_TEXT,'nullable' => true,'LENGTH'=>255,'COMMENT'=>'rating_code']);
        $connection->dropIndex($ratingTable, $installer->getIdxName(
            'rating',
            ['rating_code'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
        ));
        $connection->addIndex(
            $ratingTable,
            $installer->getIdxName(
                'rating',
                ['rating_code','entity_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['rating_code','entity_id'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
        );
        $installer->endSetup();
    }
}