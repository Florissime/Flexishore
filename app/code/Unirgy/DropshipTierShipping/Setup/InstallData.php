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

namespace Unirgy\DropshipTierShipping\Setup;

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
    const MEDIUMTEXT_SIZE = 16777216;
    const TEXT_SIZE = 65536;
    protected $categorySetupFactory;

    public function __construct(
        CategorySetupFactory $categorySetupFactory
    )
    {
        $this->categorySetupFactory = $categorySetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var \Magento\Catalog\Setup\CategorySetup $catalogSetup */
        $catalogSetup = $this->categorySetupFactory->create(['setup' => $setup]);
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udtiership_use_custom',
            [
                'type' => 'int',
                'input' => 'select',
                'label' => 'Use Product Specific Tier Shipping Rates',
                'group' => 'Dropship Tier Shipping',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 1,
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'input_renderer' => 'Unirgy\DropshipTierShipping\Block\ProductAttribute\Form\UseCustom',
                'visible_on_front' => false,
                'default' => 0
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udtiership_rates',
            [
                'type' => 'text',
                'label' => 'Tier Shipping Rates',
                'group' => 'Dropship Tier Shipping',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 1,
                'backend' => 'Unirgy\DropshipTierShipping\Model\ProductAttributeBackend\Rates',
                'input_renderer' => 'Unirgy\DropshipTierShipping\Block\ProductAttribute\Form\Rates',
                'visible_on_front' => false,
            ]
        );
    }
}