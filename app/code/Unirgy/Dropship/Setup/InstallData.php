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
use Magento\Catalog\Setup\CategorySetupFactory;
use Unirgy\Dropship\Setup\QuoteSetupFactory;
use Unirgy\Dropship\Setup\SalesSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    const MEDIUMTEXT_SIZE=16777216;
    const TEXT_SIZE=65536;
    protected $categorySetupFactory;
    protected $quoteSetupFactory;
    protected $salesSetupFactory;

    public function __construct(
        CategorySetupFactory $categorySetupFactory,
        QuoteSetupFactory $quoteSetupFactory,
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->categorySetupFactory = $categorySetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
        $this->salesSetupFactory = $salesSetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var \Magento\Catalog\Setup\CategorySetup $catalogSetup */
        $catalogSetup = $this->categorySetupFactory->create(['setup' => $setup]);
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udropship_calculate_rates',
            [
                'type' => 'int',
                'input' => 'select',
                'label' => 'Dropship Rates Calculation Type',
                'group' => 'General',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 1,
                'source' => '\Unirgy\Dropship\Model\ProductAttributeSource\CalculateRates',
                'visible_on_front' => false,
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udropship_vendor',
            [
                'type' => 'int',
                'input' => 'select',
                'label' => 'Dropship Vendor',
                'group' => 'General',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 1,
                'backend' => '\Unirgy\Dropship\Model\ResourceModel\Vendor\Backend',
                'source' => '\Unirgy\Dropship\Model\Vendor\Source',
                'input_renderer' => '\Unirgy\Dropship\Block\Vendor\Htmlselect',
                'visible_on_front' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'used_in_product_listing'=>true,
                'is_used_for_price_rules'=>true,
            ]
        );

        /** @var \Unirgy\Dropship\Setup\QuoteSetup $quoteSetup */
        $quoteSetup = $this->quoteSetupFactory->create(['setup' => $setup]);
        $qConn = $quoteSetup->getSetup()->getConnection();

        /** @var \Unirgy\Dropship\Setup\SalesSetup $salesSetup */
        $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);
        $sConn = $salesSetup->getSetup()->getConnection();

        $options = ['type' => Table::TYPE_SMALLINT, 'visible' => false, 'required' => false];
        $qEntities = ['quote','quote_address','quote_item','quote_address_item'];
        $sEntities = ['order','order_item','order_address','shipment','shipment_item','shipment_track','shipment_comment','invoice'];

        $entities = [];
        foreach ($qEntities as $qe) {
            $entities[$qe] = $quoteSetup;
        }
        foreach ($sEntities as $se) {
            $entities[$se] = $salesSetup;
        }

        $qAttributes = [
            'quote_address' => [
                'udropship_shipping_details' => ['type' => Table::TYPE_TEXT, 'length' => self::MEDIUMTEXT_SIZE]
            ],
            'quote_item' => [
                'udropship_vendor' => ['type' => Table::TYPE_INTEGER, 'length' => 10]
            ],
            'quote_address_item' => [
                'udropship_vendor' => ['type' => Table::TYPE_INTEGER, 'length' => 10]
            ],
        ];

        $sAttributes = [
            'order' => [
                'udropship_shipping_details' => ['type' => Table::TYPE_TEXT, 'length' => self::MEDIUMTEXT_SIZE],
                'udropship_status' => ['type' => Table::TYPE_SMALLINT, 'index'=>true],
                'ud_amount_fields' => ['type' => Table::TYPE_SMALLINT],
            ],
            'shipment' => [
                'udropship_vendor' => ['type' => Table::TYPE_INTEGER, 'length' => 10, 'index'=>true, 'grid'=>true],
                'udropship_status' => ['type' => Table::TYPE_SMALLINT, 'index'=>true, 'grid'=>true],
                'base_total_value' => ['type' => Table::TYPE_DECIMAL],
                'total_value' => ['type' => Table::TYPE_DECIMAL],
                'base_shipping_amount' => ['type' => Table::TYPE_DECIMAL, 'grid'=>true],
                'shipping_amount' => ['type' => Table::TYPE_DECIMAL, 'grid'=>true],
                'base_shipping_tax' => ['type' => Table::TYPE_DECIMAL],
                'shipping_tax' => ['type' => Table::TYPE_DECIMAL],
                'base_shipping_amount_incl' => ['type' => Table::TYPE_DECIMAL],
                'shipping_amount_incl' => ['type' => Table::TYPE_DECIMAL],
                'base_discount_amount' => ['type' => Table::TYPE_DECIMAL, 'grid'=>true],
                'base_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'base_hidden_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'total_cost' => ['type' => Table::TYPE_DECIMAL],
                'transaction_fee' => ['type' => Table::TYPE_DECIMAL],
                'commission_percent' => ['type' => Table::TYPE_DECIMAL],
                'handling_fee' => ['type' => Table::TYPE_DECIMAL],
                'udropship_available_at' => ['type' => Table::TYPE_DATETIME],
                'udropship_method' => ['type' => Table::TYPE_TEXT, 'length'=>64, 'grid'=>true],
                'udropship_method_description' => ['type' => Table::TYPE_TEXT, 'length'=>128, 'grid'=>true],
                'udropship_shipcheck' => ['type' => Table::TYPE_TEXT, 'length'=>5, 'index'=>true],
                'udropship_vendor_order_id' => ['type' => Table::TYPE_TEXT, 'length'=>30],
                'udropship_payout_status' => ['type' => Table::TYPE_TEXT, 'length'=>50, 'grid'=>true],
                'statement_id' => ['type' => Table::TYPE_TEXT, 'length'=>30, 'grid'=>true],
                'statement_date' => ['type' => Table::TYPE_DATETIME, 'grid'=>true],
            ],
            'order_item' => [
                'udropship_vendor' => ['type' => Table::TYPE_INTEGER, 'length' => 10],
                'ud_base_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'ud_base_hidden_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'ud_base_discount_amount' => ['type' => Table::TYPE_DECIMAL],
                'ud_base_row_total' => ['type' => Table::TYPE_DECIMAL],
                'ud_row_total' => ['type' => Table::TYPE_DECIMAL],
            ],
            'shipment_item' => [
                'qty_shipped' => ['type' => Table::TYPE_DECIMAL],
                'base_cost' => ['type' => Table::TYPE_DECIMAL],
                'vendor_sku' => ['type' => Table::TYPE_TEXT, 'length'=>255],
                'vendor_simple_sku' => ['type' => Table::TYPE_TEXT, 'length'=>255],
                'base_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'base_hidden_tax_amount' => ['type' => Table::TYPE_DECIMAL],
                'base_discount_amount' => ['type' => Table::TYPE_DECIMAL],
                'base_row_total' => ['type' => Table::TYPE_DECIMAL],
                'row_total' => ['type' => Table::TYPE_DECIMAL],
            ],
            'shipment_track' => [
                'batch_id' => ['type' => Table::TYPE_INTEGER, 'length' => 10, 'index'=>true],
                'master_tracking_id' => ['type' => Table::TYPE_TEXT, 'length'=>255],
                'package_count' => ['type' => Table::TYPE_SMALLINT],
                'package_idx' => ['type' => Table::TYPE_SMALLINT],
                'label_image' => ['type' => Table::TYPE_TEXT, 'length'=>self::TEXT_SIZE],
                'label_format' => ['type' => Table::TYPE_TEXT, 'length'=>10],
                'label_pic' => ['type' => Table::TYPE_TEXT, 'length'=>255],
                'final_price' => ['type' => Table::TYPE_DECIMAL],
                'value' => ['type' => Table::TYPE_DECIMAL],
                'length' => ['type' => Table::TYPE_DECIMAL],
                'width' => ['type' => Table::TYPE_DECIMAL],
                'height' => ['type' => Table::TYPE_DECIMAL],
                'result_extra' => ['type' => Table::TYPE_TEXT, 'length'=>self::TEXT_SIZE],
                'pkg_num' => ['type' => Table::TYPE_INTEGER],
                'int_label_image' => ['type' => Table::TYPE_TEXT, 'length'=>self::TEXT_SIZE],
                'label_render_options' => ['type' => Table::TYPE_TEXT, 'length'=>self::TEXT_SIZE],
                'udropship_status' => ['type' => Table::TYPE_TEXT, 'length'=>20, 'index'=>true],
                'next_check' => ['type' => Table::TYPE_DATETIME, 'index'=>true],
            ],
            'shipment_comment' => [
                'is_vendor_notified' => ['type' => Table::TYPE_SMALLINT],
                'is_visible_to_vendor' => ['type' => Table::TYPE_SMALLINT],
                'udropship_status' => ['type' => Table::TYPE_TEXT, 'length'=>64],
                'username' => ['type' => Table::TYPE_TEXT, 'length'=>40],
            ]
        ];

        foreach ($qAttributes as $entity=>$attrs) {
            foreach ($attrs as $attrCode => $attrOpts) {
                /** @var \Unirgy\Dropship\Setup\QuoteSetup $eSetup */
                $eSetup = $entities[$entity];
                $setup = $eSetup->getSetup();
                $conn = $setup->getConnection();
                $eSetup->addAttribute($entity, $attrCode, $attrOpts);
                $table = $eSetup->getFlatTable($entity);
                if (@$attrOpts['index']) {
                    $conn->addIndex(
                        $setup->getTable($table),
                        $conn->getIndexName(
                            $setup->getTable($table),
                            $attrCode,
                            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
                        ),
                        $attrCode
                    );
                }
            }
        }

        foreach ($sAttributes as $entity=>$attrs) {
            foreach ($attrs as $attrCode => $attrOpts) {
                /** @var \Unirgy\Dropship\Setup\SalesSetup $eSetup */
                $eSetup = $entities[$entity];
                $setup = $eSetup->getSetup();
                $conn = $setup->getConnection();
                $eSetup->addAttribute($entity, $attrCode, $attrOpts);
                $table = $eSetup->getFlatTable($entity);
                $gridTable = $table.'_grid';
                if (@$attrOpts['index']) {
                    $conn->addIndex(
                        $setup->getTable($table),
                        $conn->getIndexName(
                            $setup->getTable($table),
                            $attrCode,
                            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
                        ),
                        $attrCode
                    );
                }
                if (@$attrOpts['grid']) {
                    $conn->addIndex(
                        $setup->getTable($gridTable),
                        $conn->getIndexName(
                            $setup->getTable($gridTable),
                            $attrCode,
                            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
                        ),
                        $attrCode
                    );
                }
            }
        }

    }
}