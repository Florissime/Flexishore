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

/**
* PDF label adapter
*
* Accepts following properties:
* - batch Batch
* - vendor Vendor
* - force boolean
*/
namespace Unirgy\Dropship\Model\Label;

use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Sales\Model\Order\Shipment\Track;
use \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Helper\Label;
use \Unirgy\Dropship\Model\Vendor;

class Pdf
    extends TypeAbstract
    implements TypeInterface
{
    /**
     * @var Label
     */
    protected $_helperLabel;

    public function __construct(
        HelperData $helperData,
        DirectoryList $filesystemDirectoryList,
        Label $helperLabel,
        array $data = []
    )
    {
        $this->_helperLabel = $helperLabel;

        parent::__construct($helperData, $filesystemDirectoryList, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setContentType('application/x-pdf');
    }

    /**
    * When creating a new label, update shipment track record with filename info
    *
    * @param Track $track
    * @param array $labelImages
    */
    public function updateTrack($track, $labelImages)
    {
        $fileNames = array();
        $baseDir = $this->_fsDirList->getPath('var');
        $labelDir = $this->_fsDirList->getPath('var') . "/label/";
        /* @var \Magento\Framework\Filesystem\Directory\Write $dirWrite */
        $dirWrite = $this->_hlp->createObj('\Magento\Framework\Filesystem\Directory\WriteFactory')->create($baseDir);
        $dirWrite->create('label');

        foreach ((array)$labelImages as $i=>$label) {
           $fn = $track->getNumber().'-'.$i.'.png';
           $fileNames[] = $fn;
           file_put_contents($labelDir.$fn, base64_decode($label));
        }

        $track->setLabelImage(join("\n", $fileNames));
        $track->setLabelFormat('PDF');

        return $this;
    }

    /**
    * Generate PDF output
    *
    * @param Collection|array $tracks
    */
    public function renderTracks($tracks)
    {
        if ($this->getBatch()) {
            $batchPathName = $this->getBatchPathName($this->getBatch());
            if (!$this->getForce() && file_exists($batchPathName)) {
                //return file_get_contents($fileName);
            }
        }

        $v = $this->getVendor();
        if (!$v) {
            throw new \Exception('Vendor is not set');
        }

        $baseDir = $this->_fsDirList->getPath('var');
        $labelDir = $this->_fsDirList->getPath('var') . "/label/";
        /* @var \Magento\Framework\Filesystem\Directory\Write $dirWrite */
        $dirWrite = $this->_hlp->createObj('\Magento\Framework\Filesystem\Directory\WriteFactory')->create($baseDir);
        $dirWrite->create('label');

        $pdf = new \Zend_Pdf();
        foreach ($tracks as $track) {
            $this->beforeShipmentLabel($this->getVendor(), $track);
            if ($track->getLabelFormat()!='PDF') {
                $this->afterShipmentLabel($this->getVendor(), $track);
                continue;
            }

            $data = null;
            if (($serialized = $track->getLabelRenderOptions())) {
                $data = unserialize($serialized);
            }

            $this->setTrack($track);
            $fileNames = explode("\n", $track->getLabelImage());
            foreach ($fileNames as $i=>$fileName) {
                $__res = $this->_renderPage($pdf, $labelDir.$fileName, $data);
                if (!is_array($__res)) {
                    $__res = array($__res);
                }
                foreach ($__res as $__resOne) {
                    $pdf->pages[] = $__resOne;
                }
            }
            $this->afterShipmentLabel($this->getVendor(), $track);
        }

        if ($this->getBatch()) {
            $pdf->save($batchPathName);
            return file_get_contents($batchPathName);
        }

        return $pdf->render();
    }

    public function beforeShipmentLabel($vendor, $track)
    {
        $this->_helperLabel->beforeShipmentLabel($vendor, $track);
        return $this;
    }

    public function afterShipmentLabel($vendor, $track)
    {
        $this->_helperLabel->afterShipmentLabel($vendor, $track);
        return $this;
    }

    /**
    * Render PDF page from PNG label image
    *
    * @param \Zend_Pdf $pdf
    * @param string $fileName
    */
    protected function _renderPage(\Zend_Pdf $pdf, $fileName, $data=null)
    {
        $v = $this->getVendor();
        $image = \Zend_Pdf_Image::imageWithPath($fileName);


        $pdfPW = $v->getPdfPageWidth();
        $pdfPH = $v->getPdfPageHeight();

        $page = $pdf->newPage($v->getPdfPageSize());
        $wp = $page->getWidth()/$pdfPW;
        $hp = $page->getHeight()/$pdfPH;

        $r = (int)$v->getPdfLabelRotate();
        $l = (float)$v->getPdfLabelLeft();
        $t = (float)$v->getPdfLabelTop();
        $w = (float)$v->getPdfLabelWidth();
        $h = (float)$v->getPdfLabelHeight();

        if (!is_null($data)) {
            extract($data);
            if (!empty($data['w'])) {
                if ($r==90 || $r==270) {
                    $pdfPW = $h+$t*2;
                } else {
                    $pdfPW = $w+$l*2;
                }
                $wp = $page->getWidth()/$pdfPW;
            }
            if (!empty($data['h'])) {
                if ($r==90 || $r==270) {
                    $pdfPH = $w+$l*2;
                } else {
                    $pdfPH = $h+$t*2;
                }
                $hp = $page->getHeight()/$pdfPH;
            }
        }

        if ($r==90 || $r==270) {
            $tmp = $w; $w = $h; $h = $tmp;
        }
        $b = $pdfPH-$t-$h;
        $page->drawImage($image, $l*$wp, $b*$hp, ($l+$w)*$wp, ($b+$h)*$hp);

        return $page;
    }

    public function renderBatchContent($batch=null)
    {
        if (is_null($batch)) {
            $batch = $this->getBatch();
        } else {
            $this->setBatch($batch);
        }

        $this->setVendor($batch->getVendor());

        return array(
            'content' => $this->renderTracks($batch->getBatchTracks()),
            'filename' => $this->getBatchFileName($batch),
            'type' => $this->getContentType(),
        );
    }

    public function renderTrackContent($track=null)
    {
        if (is_null($track)) {
            $track = $this->getTrack();
        } else {
            $this->setTrack($track);
        }

        $this->setVendor($this->_getTrackVendor($track));

        return array(
            'content' => $this->renderTracks(array($track)),
            'filename' => $track->getNumber().'.pdf',
            'type' => $this->getContentType(),
        );
    }

    public function getBatchFileName($batch)
    {
        return 'label_batch-'.$batch->getId().'.pdf';
    }
}
