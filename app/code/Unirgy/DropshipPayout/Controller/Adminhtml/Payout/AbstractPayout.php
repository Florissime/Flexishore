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
 * @package    Unirgy_DropshipPayout
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Unirgy\DropshipPayout\Helper\Data as HelperData;
use Unirgy\DropshipPayout\Model\PayoutFactory;

abstract class AbstractPayout extends Action
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @var HelperData
     */
    protected $_payoutHlp;

    /**
     * @var PayoutFactory
     */
    protected $_payoutFactory;

    protected $_hlp;

    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        HelperData $payoutHelper,
        PayoutFactory $payoutFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        Context $context
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_payoutHlp = $payoutHelper;
        $this->_payoutFactory = $payoutFactory;
        $this->_fileFactory = $fileFactory;
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct($context);
    }

    protected function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Unirgy_Dropship::statement');
        $resultPage->getConfig()->getTitle()->prepend(__('Vendor Payouts'));
        $resultPage->addBreadcrumb(__('Sales'), __('Sales'));
        $resultPage->addBreadcrumb(__('Dropship'), __('Dropship'));
        $resultPage->addBreadcrumb(__('Vendor Payouts'), __('Vendor Payouts'));
        return $resultPage;

    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Unirgy_DropshipPayout::payouts');
    }
}
