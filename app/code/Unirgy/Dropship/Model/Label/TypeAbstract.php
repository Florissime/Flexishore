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

namespace Unirgy\Dropship\Model\Label;

use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Framework\DataObject;
use \Unirgy\Dropship\Helper\Data as HelperData;

abstract class TypeAbstract extends DataObject
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var DirectoryList
     */
    protected $_fsDirList;

    public function __construct(
        HelperData $helperData,
        DirectoryList $filesystemDirectoryList,
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        $this->_fsDirList = $filesystemDirectoryList;

        parent::__construct($data);
        $this->_construct();
    }

    protected function _construct()
    {

    }

    /**
     * Send batch file PDF download
     *
     */
    public function printBatch($batch=null)
    {
        $data = $this->renderBatchContent($batch);
        $this->_hlp->sendDownload($data['filename'], $data['content'], $data['type']);
    }

    /**
     * Send PDF download only for 1 track
     *
     * @param Track $track
     */
    public function printTrack($track=null)
    {
        $data = $this->renderTrackContent($track);
        $this->_hlp->sendDownload($data['filename'], $data['content'], $data['type']);
    }

    public function getBatchPathName($batch)
    {
        $baseDir = $this->_fsDirList->getPath('var');
        $batchDir = $this->_fsDirList->getPath('var') . "/batch/";
        /* @var \Magento\Framework\Filesystem\Directory\Write $dirWrite */
        $dirWrite = $this->_hlp->createObj('\Magento\Framework\Filesystem\Directory\WriteFactory')->create($baseDir);
        $dirWrite->create('batch');
        return $batchDir.$this->getBatchFileName($batch);
    }

    protected function _getTrackVendorId($track)
    {
        $vId = null;
        if ($track instanceof \Unirgy\Rma\Model\Rma\Track) {
            $vId = $track->getRma()->getUdropshipVendor();
        } elseif ($track instanceof \Magento\Sales\Model\Order\Shipment\Track) {
            $vId = $track->getShipment()->getUdropshipVendor();
        }
        return $vId;
    }

    protected function _getTrackVendor($track)
    {
        return $this->_hlp->getVendor($this->_getTrackVendorId($track));
    }
}