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
 * Uploader block for Wysiwyg Images
 *
 * @category   Mage
 * @package    \Magento\Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
*/
namespace Unirgy\Dropship\Block\Vendor\Wysiwyg\Images\Content;

use \Magento\Backend\Block\Media\Uploader as MediaUploader;
use \Magento\Backend\Block\Template\Context;
use \Magento\Framework\File\Size;
use \Magento\Framework\Url;
use \Unirgy\Dropship\Model\Wysiwyg\Images\Storage;

class Uploader extends MediaUploader
{
    /**
     * @var Storage
     */
    protected $_imagesStorage;

    public function __construct(
        Storage $imagesStorage,
        Context $context,
        Size $fileSize, 
        array $data = []
    )
    {
        $this->_imagesStorage = $imagesStorage;

        parent::__construct($context, $fileSize, $data);
        $this->setTemplate('Unirgy_Dropship::unirgy/dropship/vendor/wysiwyg/browser/content/uploader.phtml');
        $params = $this->getConfig()->getParams();
        $type = $this->_getMediaType();
        $allowed = $this->_imagesStorage->getAllowedExtensions($type);
        $labels = array();
        $files = array();
        foreach ($allowed as $ext) {
            $labels[] = '.' . $ext;
            $files[] = '*.' . $ext;
        }
        $this->getConfig()
            ->setUrl($this->_urlBuilder->addSessionParam()->getUrl('*/*/upload', array('type' => $type)))
            ->setParams($params)
            ->setFileField('image')
            ->setFilters(array(
                'images' => array(
                    'label' => __('Images (%1)', implode(', ', $labels)),
                    'files' => $files
                )
            ));
    }

    /**
     * Return current media type based on request or data
     * @return string
     */
    protected function _getMediaType()
    {
        if ($this->hasData('media_type')) {
            return $this->_getData('media_type');
        }
        return $this->getRequest()->getParam('type');
    }
}
