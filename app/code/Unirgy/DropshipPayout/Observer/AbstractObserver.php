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
 
namespace Unirgy\DropshipPayout\Observer;

use Psr\Log\LoggerInterface;
use Unirgy\DropshipPayout\Helper\Data as HelperData;
use Unirgy\DropshipPayout\Helper\ProtectedCode;
use Unirgy\DropshipPayout\Model\PayoutFactory;

abstract class AbstractObserver
{
    /**
     * @var PayoutFactory
     */
    protected $_payoutFactory;

    /**
     * @var HelperData
     */
    protected $_payoutHlp;

    /**
     * @var ProtectedCode
     */
    protected $_payoutHlpPr;

    protected $_hlp;

    public function __construct(
        PayoutFactory $modelPayoutFactory,
        HelperData $helperData,
        ProtectedCode $helperProtectedCode,
        \Unirgy\Dropship\Helper\Data $udropshipHelper
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_payoutFactory = $modelPayoutFactory;
        $this->_payoutHlp = $helperData;
        $this->_payoutHlpPr = $helperProtectedCode;
    }

    public function processStandard()
    {
        session_write_close();
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        $payouts = $this->_payoutFactory->create()->getCollection()
            ->setFlag('skip_offline', true)
            ->loadScheduledPayouts()
            ->addPendingPos(true)
            ->finishPayout()
            ->saveOrdersPayouts(true);

        try {
            $payouts->pay();
        } catch (\Exception $e) {
            $this->_hlp->logError($e);
        }
        
            
        $this->_payoutHlp->generateSchedules()->cleanupSchedules();
    }
    


    protected function _sales_order_shipment_save_after($observer)
    {
        $po = $observer->getEvent()->getShipment();
        $this->_payoutHlpPr->sales_order_shipment_save_after($po);
    }

    protected function _udpo_po_save_after($observer)
    {
        $po = $observer->getEvent()->getPo();
        $this->_payoutHlpPr->udpo_po_save_after($po);
    }
}
