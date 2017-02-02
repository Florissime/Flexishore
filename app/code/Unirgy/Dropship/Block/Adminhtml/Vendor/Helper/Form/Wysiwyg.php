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

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form;

use \Magento\Framework\Data\Form\Element\CollectionFactory;
use \Magento\Framework\Data\Form\Element\Factory;
use \Magento\Framework\Data\Form\Element\Textarea;
use \Magento\Framework\Escaper;

class Wysiwyg extends Textarea
{
    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $_backendHelper;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;

    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Backend\Helper\Data $backendHelper,
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        $data = []
    )
    {
        $this->_layout = $layout;
        $this->_backendHelper = $backendHelper;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    public function getAfterElementHtml()
    {
        $html = parent::getAfterElementHtml();
        $html .= $this->_layout
            ->createBlock('\Magento\Backend\Block\Widget\Button', '', array(
                'label'   => __('WYSIWYG Editor'),
                'type'    => 'button',
                'disabled' => false,
                'class' => '',
                'onclick' => 'uVendorWysiwygEditor.open(\''.$this->_backendHelper->getUrl('*/*/wysiwyg').'\', \''.$this->getHtmlId().'\')'
            ))->toHtml();
        return $html;
    }

}

