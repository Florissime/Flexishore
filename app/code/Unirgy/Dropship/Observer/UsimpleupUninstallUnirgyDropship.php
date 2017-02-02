<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class UsimpleupUninstallUnirgyDropship extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $this->_hlp->getObj('\Unirgy\SimpleUp\Helper\Data')->removeFiles('
app/etc/modules/\Unirgy\Dropship.xml
app/code/community/Unirgy/Dropship/
app/code/community/Unirgy/DropshipHelper/
app/design/adminhtml/default/default/layout/udropship.xml
app/design/adminhtml/default/default/template/udropship/
app/design/frontend/base/default/layout/udropship.xml
app/design/frontend/base/default/template/unirgy/dropship/
app/design/frontend/default/default/layout/udropship.xml
app/design/frontend/default/default/template/unirgy/dropship/
app/locale/en_US/\Unirgy\Dropship.csv
app/locale/en_US/template/email/udropship_password.html
app/locale/en_US/template/email/udropship_statement.html
app/locale/en_US/template/email/udropship_vendor.html
app/locale/en_US/template/email/udropship_vendor_notify_lowstock.html
app/locale/en_US/template/email/udropship_vendor_shipment_comment.html
skin/frontend/base/default/css/udropship.css
skin/frontend/default/default/css/udropship.css
app/design/frontend/default/udropship/
skin/frontend/default/udropship/
js/udropship.js
        ');
    }
}
