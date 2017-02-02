<?php

namespace Unirgy\Dropship\Controller\Vendor;

class ReprintLabelBatch extends AbstractVendor
{
    public function execute()
    {
        $hlp = $this->_hlp;

        if (($trackId = $this->getRequest()->getParam('track_id'))) {
            /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
            $track = $this->_hlp->createObj('\Magento\Sales\Model\Order\Shipment\Track')->load($trackId);
            if (!$track->getId()) {
                return;
            }
            $labelModel = $hlp->getLabelTypeInstance($track->getLabelFormat());
            $labelModel->printTrack($track);
        }

        if (($batchId = $this->getRequest()->getParam('batch_id'))) {
            /** @var \Unirgy\Dropship\Model\Label\Batch $batch */
            $batch = $this->_hlp->createObj('\Unirgy\Dropship\Model\Label\Batch')->load($batchId);
            if (!$batch->getId()) {
                return;
            }
            $labelModel = $this->_hlp->getLabelTypeInstance($batch->getLabelType());
            $labelModel->printBatch($batch);
        }
    }
}
