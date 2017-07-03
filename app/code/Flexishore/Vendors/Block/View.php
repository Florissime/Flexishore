<?php
namespace Flexishore\Vendors\Block;
class View extends \Magento\Framework\View\Element\Template
{
    protected $request;

    public $vendorHelper;
    public $registry;
    public $unHelper;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = [],
        \Flexishore\Vendors\Helper\Data $vendorHelper,
        \Unirgy\Dropship\Helper\Data $unHelper,
        \Magento\Framework\Registry $registry
    )
    {
        $this->validator = $context->getValidator();
        $this->resolver = $context->getResolver();
        $this->_filesystem = $context->getFilesystem();
        $this->templateEnginePool = $context->getEnginePool();
        $this->_storeManager = $context->getStoreManager();
        $this->_appState = $context->getAppState();
        $this->templateContext = $this;
        $this->pageConfig = $context->getPageConfig();
        $this->vendorHelper = $vendorHelper;
        $this->registry = $registry;
        $this->unHelper = $unHelper;
        parent::__construct($context, $data);
    }
}
