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

namespace Unirgy\Dropship\Controller\Adminhtml\Batch;

use \Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

abstract class AbstractBatch extends Action
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Unirgy_Dropship::label_batch');
    }

    /**
     * @return Page
     */
    protected function _initAction()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu("Unirgy_Dropship::label_batch")
            ->addBreadcrumb(__('Sales'), __('Sales'))
            ->addBreadcrumb(__('Dropship'), __('Dropship'))
            ->addBreadcrumb(__('Label Batches'), __('Label Batches'));
        return $resultPage;
    }
}