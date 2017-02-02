<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Batch;

use \Magento\Backend\App\Action\Context;
use \Unirgy\Dropship\Model\Label\Batch;

class BatchLabels extends AbstractBatch
{
    /**
     * @var Batch
     */
    protected $_labelBatch;

    public function __construct(Context $context, 
        Batch $labelBatch)
    {
        $this->_labelBatch = $labelBatch;

        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('batch_id');
        $batch = $this->_labelBatch->load($id);
        $batch->prepareLabelsDownloadResponse();
    }
}
