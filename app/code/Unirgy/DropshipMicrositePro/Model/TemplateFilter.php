<?php

namespace Unirgy\DropshipMicrositePro\Model;

use Magento\Email\Model\Source\Variables;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Escaper;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Variable\Model\VariableFactory;
use Magento\Widget\Model\ResourceModel\Widget;
use Magento\Widget\Model\Template\Filter;
use Magento\Widget\Model\Widget as ModelWidget;
use Psr\Log\LoggerInterface;
use Unirgy\DropshipMicrosite\Helper\Data as HelperData;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;

class TemplateFilter extends Filter
{
    /**
     * @var HelperData
     */
    protected $_msHlp;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    public function __construct(StringUtils $string,
        LoggerInterface $logger, 
        Escaper $escaper, 
        Repository $assetRepo, 
        ScopeConfigInterface $scopeConfig, 
        VariableFactory $coreVariableFactory, 
        StoreManagerInterface $storeManager, 
        LayoutInterface $layout, 
        LayoutFactory $layoutFactory, 
        State $appState, 
        UrlInterface $urlModel, 
        \Pelago\Emogrifier $emogrifier, 
        Variables $configVariables, 
        Widget $widgetResource, 
        ModelWidget $widget, 
        HelperData $helperData, 
        DropshipHelperData $dropshipHelperData
    )
    {
        $this->_msHlp = $helperData;
        $this->_hlp = $dropshipHelperData;

        parent::__construct($string, $logger, $escaper, $assetRepo, $scopeConfig, $coreVariableFactory, $storeManager, $layout, $layoutFactory, $appState, $urlModel, $emogrifier, $configVariables, $widgetResource, $widget);
        $_vendor = $this->_msHlp->getCurrentVendor();
        if ($_vendor) {
            $this->templateVars['currentVendor'] = $this->_msHlp->getCurrentVendor();
            $this->templateVars['vacationStatus'] = $this->_msHlp->getCurrentVendor()->getVacationStatus()*1;
            if ($this->_hlp->isModuleActive('Unirgy_DropshipVendorRatings')) {
                $this->templateVars['currentVendorReviewsSummaryHtml'] = $this->_hlp->getObj('Unirgy\DropshipVendorRatings\Helper\Data')->getReviewsSummaryHtml($_vendor);
            }
            $this->templateVars['currentVendorLandingPageTitle'] = $this->_msHlp->getLandingPageTitle($_vendor);
        }
    }
}