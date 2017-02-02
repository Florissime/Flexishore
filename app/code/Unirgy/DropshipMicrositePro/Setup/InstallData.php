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

namespace Unirgy\DropshipMicrositePro\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData extends  \Magento\Cms\Setup\InstallData
{
    const MEDIUMTEXT_SIZE=16777216;
    const TEXT_SIZE=65536;
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $blockContent = <<<EOT
<h1 class="vendor-name">{{var currentVendorLandingPageTitle|escape:html}}</h1>
<p>{{var currentVendorReviewsSummaryHtml|raw}}</p>
<div class="generic-box vendor-description"><img class="vendor-img" src="{{media url=\$currentVendor.getLogo()}}" alt="" /> {{var currentVendor.getDescription()|escape:html}}</div>
<div id="our-products">{{layout handle="umicrosite_current_vendor_products_list"}}</div>
EOT;
        $defaultLandingPageData = [
            'title' => 'Default Microsite Vendor Landing Page',
            'page_layout' => '2columns-left',
            'identifier' => 'default-microsite-vendor-landing-page',
            'content_heading' => 'Default Microsite Vendor Landing Page',
            'content' => $blockContent,
            'is_active' => 1,
            'stores' => [0],
            'sort_order' => 0
        ];
        $defaultLandingPage = $this->createPage()->load('default-microsite-vendor-landing-page', 'identifier');
        if (!$defaultLandingPage->getId()) {
            $defaultLandingPage->setData($defaultLandingPageData)->save();
        }
    }
}