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

namespace Unirgy\Dropship\Block\Vendor\Wysiwyg\Form;

use \Magento\Cms\Model\Wysiwyg\Config;
use \Magento\Framework\Data\Form;
use \Magento\Framework\View\Element\AbstractBlock;
use \Magento\Framework\View\Element\Context;

class Content extends AbstractBlock
{
    /**
     * @var Config
     */
    protected $_wysiwygConfig;

    public function __construct(Context $context, 
        array $data = [], 
        Config $wysiwygConfig = null)
    {
        $this->_wysiwygConfig = $wysiwygConfig;

        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        $form = new Form(array('id' => 'wysiwyg_edit_form', 'action' => $this->getData('action'), 'method' => 'post'));
        $form->addType('editor', '\Unirgy\Dropship\Block\Vendor\Wysiwyg\Form\Element\Editor');

        $config['document_base_url']     = $this->getData('store_media_url');
        $config['store_id']              = $this->getData('store_id');
        $config['add_variables']         = false;
        $config['add_widgets']           = false;
        $config['add_directives']        = true;
        $config['use_container']         = true;
        $config['container_class']       = 'hor-scroll';
        $config['directives_url']        = $this->getUrl('udropship/vendor_wysiwyg/directive');
        $config['files_browser_window_url'] = $this->getUrl('udropship/vendor_wysiwyg_images/index');
        
        $form->addField($this->getData('editor_element_id'), 'editor', array(
            'name'      => 'content',
            'style'     => 'width:725px;height:460px',
            'required'  => true,
            'force_load' => true,
            'config'    => $this->_wysiwygConfig->getConfig($config)
        ));

        return $form->toHtml();
    }
}
