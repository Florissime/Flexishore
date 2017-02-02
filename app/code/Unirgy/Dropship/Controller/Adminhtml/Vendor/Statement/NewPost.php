<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

use \Magento\Backend\App\Action\Context;
use \Magento\Backend\Helper\Data as BackendHelperData;
use \Magento\Framework\Controller\Result\RedirectFactory;
use \Magento\Framework\Date;
use \Magento\Framework\Model\Locale;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Model\Vendor;

class NewPost extends AbstractStatement
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    public function __construct(
        HelperData $helper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Backend\App\Action\Context $context
    )
    {
        $this->_hlp = $helper;
        $this->_timezone = $timezone;

        parent::__construct($udropshipHelper, $registry, $resultForwardFactory, $resultPageFactory, $resultRedirectFactory, $fileFactory, $context);
    }

    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->_resultRedirectFactory->create()->setPath('new');
        }
        $hlp = $this->_hlp;

        $dateFrom = $this->getRequest()->getParam('date_from');
        $dateTo = $this->getRequest()->getParam('date_to');

        $datetimeFormatInt = \Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT;
        $dateFormat = $this->_timezone->getDateFormat(\IntlDateFormatter::SHORT);
        if ($this->getRequest()->getParam('use_locale_timezone')) {
            $dateFrom = $this->_hlp->dateLocaleToInternal($dateFrom, $dateFormat, true);
            $dateTo = $this->_hlp->dateLocaleToInternal($dateTo, $dateFormat, true);
            $dateTo = $this->_timezone->date($dateTo, null, false);
            $dateTo->add(new \DateInterval('P1D'));
            $dateTo->sub(new \DateInterval('PT1S'));
            $dateTo = datefmt_format_object($dateTo, $datetimeFormatInt);
        } else {
            $dateFrom = $this->_timezone->date($dateFrom, null, false);
            $dateFrom = datefmt_format_object($dateFrom, $datetimeFormatInt);
            $dateTo = $this->_timezone->date($dateTo, null, false);
            $dateTo->add(new \DateInterval('P1D'));
            $dateTo->sub(new \DateInterval('PT1S'));
            $dateTo = datefmt_format_object($dateTo, $datetimeFormatInt);
        }

        if ($this->getRequest()->getParam('all_vendors')) {
            $vendors = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor')->getCollection()
                ->addFieldToFilter('status', 'A')
                ->getAllIds();
        } else {
            $vendors = $this->getRequest()->getParam('vendor_ids');
        }
        $period = $this->getRequest()->getParam('statement_period');
        if (!$period) {
            $period = date('ym', strtotime($dateFrom));
        }

        $n = sizeof($vendors);
        $i = 0;
        ob_implicit_flush();
        echo "<html><body>Generating {$n} vendor statements<hr/>";

        $generator = $this->_hlp->createObj('\Unirgy\Dropship\Model\Pdf\Statement');
        foreach ($vendors as $vId) {
            echo "Vendor ID {$vId} (".(++$i)."/{$n}): ";
            try {
                $statement = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor\Statement');
                if ($statement->load("{$vId}-{$period}", 'statement_id')->getId()) {
                    echo "<span style='color:#888'>ALREADY EXISTS</span>.<br/>";
                    continue;
                }
                $statement->addData(array(
                    'vendor_id' => $vId,
                    'order_date_from' => $dateFrom,
                    'order_date_to' => $dateTo,
                    'statement_id' => "{$vId}-{$period}",
                    'statement_date' => $this->_hlp->now(),
                    'statement_period' => $period,
                    'statement_filename' => "statement-{$vId}-{$period}.pdf",
                    'created_at' => $this->_hlp->now(),
                    'use_locale_timezone' => $this->getRequest()->getParam('use_locale_timezone')
                ));

                $statement->fetchOrders();

                $statement->save();
            } catch (\Exception $e) {
                echo "<span style='color:#F00'>ERROR</span>: ".$e->getMessage()."<br/>";
                continue;
            }
            echo "<span style='color:#0F0'>DONE</span>.<br/>";
        }

        $redirectUrl = $this->_helper->getUrl('*/*/index');
        echo "<hr>".__('All done, <a href="%1">click here</a> to be redirected to statements grid.', $redirectUrl);
        exit;
    }
}
