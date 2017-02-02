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

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\Wysiwyg;

use \Magento\Backend\Block\Template\Context;
use \Magento\Backend\Block\Widget\Form;
use \Magento\Cms\Model\Wysiwyg\Config;
use \Magento\Framework\Data\Form as DataForm;

class Content extends Form
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

    protected function _prepareForm()
    {
        $form = new DataForm(array('id' => 'wysiwyg_edit_form', 'action' => $this->getData('action'), 'method' => 'post'));

        $config['document_base_url']     = $this->getData('store_media_url');
        $config['store_id']              = $this->getData('store_id');
        $config['add_variables']         = false;
        $config['add_widgets']           = false;
        $config['add_directives']        = true;
        $config['use_container']         = true;
        $config['container_class']       = 'hor-scroll';

        $form->addField($this->getData('editor_element_id'), 'editor', array(
            'name'      => 'content',
            'style'     => 'width:725px;height:460px',
            'required'  => true,
            'force_load' => true,
            'config'    => $this->_wysiwygConfig->getConfig($config)
        ));
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
