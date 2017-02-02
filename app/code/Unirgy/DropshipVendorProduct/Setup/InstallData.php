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

namespace Unirgy\DropshipVendorProduct\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Catalog\Setup\CategorySetupFactory;
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

    public function __construct(
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->categorySetupFactory = $categorySetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var \Magento\Catalog\Setup\CategorySetup $catalogSetup */
        $catalogSetup = $this->categorySetupFactory->create(['setup' => $setup]);
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udprod_attributes_changed',
            [
                'type' => 'text',
                'label' => 'uMarketplace Changed Data',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 0,
                'backend' => '\Magento\Eav\Model\Entity\Attribute\Backend\Serialized',
                'visible_on_front' => false,
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udprod_cfg_simples_added',
            [
                'type' => 'text',
                'label' => 'uMarketplace Simples Added',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 0,
                'backend' => '\Magento\Eav\Model\Entity\Attribute\Backend\Serialized',
                'visible_on_front' => false,
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udprod_cfg_simples_removed',
            [
                'type' => 'text',
                'label' => 'uMarketplace Simples Removed',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 0,
                'backend' => '\Magento\Eav\Model\Entity\Attribute\Backend\Serialized',
                'visible_on_front' => false,
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udprod_fix_description',
            [
                'type' => 'text',
                'input'=>'textarea',
                'label' => 'Fix Description',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'group'=>'Fixes Required From Vendor',
                'user_defined' => 1,
                'required' => 0,
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udprod_pending_notified',
            [
                'type' => 'int',
                'label' => 'uMarketplace Pending Notified',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 0,
                'visible_on_front' => false,
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udprod_approved_notified',
            [
                'type' => 'int',
                'label' => 'uMarketplace Approved Notified',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 0,
                'visible_on_front' => false,
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udprod_fix_notified',
            [
                'type' => 'int',
                'label' => 'uMarketplace Fix Notified',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 0,
                'visible_on_front' => false,
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udprod_pending_admin_notified',
            [
                'type' => 'int',
                'label' => 'uMarketplace Admin Pending Notified',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 0,
                'visible_on_front' => false,
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udprod_approved_admin_notified',
            [
                'type' => 'int',
                'label' => 'uMarketplace Admin Approved Notified',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 0,
                'visible_on_front' => false,
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udprod_fix_admin_notified',
            [
                'type' => 'int',
                'label' => 'uMarketplace Admin Fix Notified',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 0,
                'visible_on_front' => false,
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udprod_pending_notify',
            [
                'type' => 'int',
                'label' => 'uMarketplace Pending Notify',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 0,
                'visible_on_front' => false,
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udprod_approved_notify',
            [
                'type' => 'int',
                'label' => 'uMarketplace Approved Notify',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 0,
                'visible_on_front' => false,
            ]
        );
        $catalogSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'udprod_fix_notify',
            [
                'type' => 'int',
                'label' => 'uMarketplace Fix Notify',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'required' => 0,
                'visible' => 0,
                'visible_on_front' => false,
            ]
        );

    }
}