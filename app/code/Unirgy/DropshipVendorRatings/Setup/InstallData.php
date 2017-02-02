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

namespace Unirgy\DropshipVendorRatings\Setup;

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
    protected $_ratingHlp;

    public function __construct(
        \Unirgy\DropshipVendorRatings\Helper\Data $udratingHelper
    )
    {
        $this->_ratingHlp = $udratingHelper;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $conn = $setup->getConnection();

        $myEt = $this->_ratingHlp->myEt();

        if (($row = $installer->getTableRow('rating_entity', 'entity_id', $myEt))) {
            if ($row['entity_code']!='udropship_vendor') {
                throw new \Exception(__("entity_id=%s is already used in rating_entity. Change it in %s.",
                    $myEt, '\Unirgy\DropshipVendorRatings\Helper\Data::$_myEt'
                ));
            }
        } else {
            $conn->insert($installer->getTable('rating_entity'), array('entity_id'=>$myEt,'entity_code'=>'udropship_vendor'));
        }
        if (($row = $installer->getTableRow('review_entity', 'entity_id', $myEt))) {
            if ($row['entity_code']!='udropship_vendor') {
                throw new \Exception(__("entity_id=%s is already used in review_entity. Change it in %s.",
                    $myEt, '\Unirgy\DropshipVendorRatings\Helper\Data::$_myEt'
                ));
            }
        } else {
            $conn->insert($installer->getTable('review_entity'), array('entity_id'=>$myEt,'entity_code'=>'udropship_vendor'));
        }
    }
}