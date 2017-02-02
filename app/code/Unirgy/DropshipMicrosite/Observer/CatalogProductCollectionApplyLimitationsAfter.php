<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\DesignInterface;
use Unirgy\DropshipMicrosite\Helper\Data as HelperData;
use Unirgy\Dropship\Helper\Item;

class CatalogProductCollectionApplyLimitationsAfter extends AbstractObserver implements ObserverInterface
{
    /**
     * @var DesignInterface
     */
    protected $_viewDesign;

    public function __construct(
        DesignInterface $viewDesignInterface,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        HelperData $micrositeHelper,
        Item $helperItem
    )
    {
        $this->_viewDesign = $viewDesignInterface;

        parent::__construct($scopeConfig, $httpHeader, $udropshipHelper, $micrositeHelper, $helperItem);
    }

    public function execute(Observer $observer)
    {
        $vendor = $this->_getVendor();
        $collection = $observer->getEvent()->getCollection();
        try {
            if ($vendor) {
                $collection->addAttributeToFilter('udropship_vendor', $vendor->getId());
echo 1;
            } elseif ($this->_viewDesign->getArea()=='frontend') {
echo 2;
                $res = $this->_hlp->rHlp();
                $sql = "select vendor_id from {$res->getTableName('udropship_vendor')} where status='A'";
                $session = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
                if ($session->isLoggedIn() && $session->getVendor()->getStatus()=='I') {
                    $sql .= " OR vendor_id=".$session->getVendor()->getId();
                }
                $collection->addAttributeToFilter('udropship_vendor', ['in'=>new \Zend_Db_Expr($sql)]);
                //$collection->joinAttribute('udropship_vendor', 'catalog_product/udropship_vendor', 'entity_id');
                //$collection->joinField('udropship_status', 'udropship/vendor', 'status', 'vendor_id=udropship_vendor', $cond);
            }
        } catch (\Exception $e) {
            $skip = [
                'Joined field with this alias is already declared',
                'Invalid alias, already exists in joined attributes',
            ];
            if (!in_array($e->getMessage(), $skip)) {
                throw $e;
            }
        }
    }
}
