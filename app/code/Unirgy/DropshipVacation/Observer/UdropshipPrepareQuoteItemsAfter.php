<?php

namespace Unirgy\DropshipVacation\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipVacation\Model\Source;

class UdropshipPrepareQuoteItemsAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        foreach ($observer->getItems() as $item) {
            $iVendor = $this->_hlp->getVendor($item->getUdropshipVendor());
            if (Source::MODE_VACATION_NOTIFY == $iVendor->getData('vacation_mode')) {
                if (($message = $this->_hlp->getScopeConfig('udropship/customer/vacation_message'))) {
                    if ($iVendor->getData('vacation_end')) {
                        $now = $this->_localeDate->date(
                            null,
                            null,
                            false
                        );
                        $vacationEnd = $this->_localeDate->date(
                            strtotime($iVendor->getData('vacation_end')),
                            null,
                            false
                        );
                        if ($now<$vacationEnd) {
                            $formatter = new \IntlDateFormatter($this->_localeResolver->getLocale(), null, null);
                            $format = $this->_localeDate->getDateFormat(\IntlDateFormatter::MEDIUM);
                            $formatter->setPattern($format);
                            $vacationEndStr = $formatter->format($vacationEnd);
                            $message = str_replace('{{vacation_end}}', $vacationEndStr, $message);
                            $item->setMessage($message);
                        }
                    }
                }
            }
        }
    }
}
