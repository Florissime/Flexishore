<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     \Magento\Adminhtml
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Directoty tree renderer for Cms Wysiwyg Images
 *
 * @category   Mage
 * @package    \Magento\Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */

namespace Unirgy\Dropship\Block\Vendor\Wysiwyg\Images;

use \Magento\Framework\Registry;
use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Unirgy\Dropship\Helper\Wysiwyg\Images;

class Tree extends Template
{
    /**
     * @var Images
     */
    protected $_wysiwygImages;

    /**
     * @var Registry
     */
    protected $_frameworkRegistry;

    public function __construct(
        Images $wysiwygImages,
        Registry $frameworkRegistry,
        Context $context,
        array $data = []
    )
    {
        $this->_wysiwygImages = $wysiwygImages;
        $this->_frameworkRegistry = $frameworkRegistry;

        parent::__construct($context, $data);
    }

    public function getTreeJson()
    {
        $helper = $this->_wysiwygImages;
        $storageRoot = $helper->getStorageRoot();
        $collection = $this->_frameworkRegistry->registry('storage')->getDirsCollection($helper->getCurrentPath());
        $jsonArray = array();
        foreach ($collection as $item) {
            $jsonArray[] = array(
                'text'  => $helper->getShortFilename($item->getBasename(), 20),
                'id'    => $helper->convertPathToId($item->getFilename()),
                'cls'   => 'folder'
            );
        }
        return \Zend_Json::encode($jsonArray);
    }

    public function getTreeLoaderUrl()
    {
        return $this->getUrl('*/*/treeJson');
    }

    public function getRootNodeName()
    {
        return __('Storage Root');
    }

    public function getTreeCurrentPath()
    {
        $treePath = '/root';
        if ($path = $this->_frameworkRegistry->registry('storage')->getSession()->getCurrentPath()) {
            $helper = $this->_wysiwygImages;
            $path = str_replace($helper->getStorageRoot(), '', $path);
            $relative = '';
            foreach (explode('/', $path) as $dirName) {
                if ($dirName) {
                    $relative .= '/' . $dirName;
                    $treePath .= '/' . $helper->idEncode($relative);
                }
            }
        }
        return $treePath;
    }
}
