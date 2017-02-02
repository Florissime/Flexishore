<?php

namespace Unirgy\Dropship\Controller\Vendor\Wysiwyg;

use Magento\Email\Model\Template\FilterFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Image\Adapter;

class Directive extends AbstractWysiwyg
{
    /**
     * @var AbstractHelper
     */
    protected $_helperAbstractHelper;

    /**
     * @var FilterFactory
     */
    protected $_templateFilterFactory;

    public function __construct(Context $context, 
        AbstractHelper $helperAbstractHelper, 
        FilterFactory $templateFilterFactory)
    {
        $this->_helperAbstractHelper = $helperAbstractHelper;
        $this->_templateFilterFactory = $templateFilterFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $directive = $this->getRequest()->getParam('___directive');
        $directive = $this->_helperAbstractHelper->urlDecode($directive);
        $url = $this->_templateFilterFactory->create()->filter($directive);
        try {
            $image = Adapter::factory('GD2');
            $image->open($url);
            $image->display();
        } catch (\Exception $e) {
            $image = imagecreate(100, 100);
            $bkgrColor = imagecolorallocate($image,10,10,10);
            imagefill($image,0,0,$bkgrColor);
            $textColor = imagecolorallocate($image,255,255,255);
            imagestring($image, 4, 10, 10, 'Skin image', $textColor);
            header('Content-type: image/png');
            imagepng($image);
            imagedestroy($image);
        }
    }
}
