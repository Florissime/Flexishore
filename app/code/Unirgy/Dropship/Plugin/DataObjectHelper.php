<?php
namespace Unirgy\Dropship\Plugin;

class DataObjectHelper
{
    public function aroundPopulateWithArray(\Magento\Framework\Api\DataObjectHelper $subject, \Closure $next, $dataObject, array $data, $interfaceName)
    {
        if ($dataObject instanceof \Magento\Sales\Model\Order) {
            foreach (['udropship_shipping_details'] as $key) {
                if (isset($data[$key])) {
                    $dataObject->setData($key, $data[$key]);
                }
            }
        }
        if ($dataObject instanceof \Magento\Sales\Model\Order\Item) {
            foreach (['udropship_vendor'] as $key) {
                if (isset($data[$key])) {
                    $dataObject->setData($key, $data[$key]);
                }
            }
        }
        return $next($dataObject, $data, $interfaceName);
    }
    public function aroundMergeDataObjects(
        \Magento\Framework\Api\DataObjectHelper $subject,
        \Closure $next,
        $interfaceName, $firstDataObject, $secondDataObject
    )
    {
        if ($firstDataObject instanceof \Magento\Sales\Model\Order) {
            foreach (['udropship_shipping_details'] as $key) {
                $firstDataObject->setData($key, $secondDataObject->getData($key));
            }
        }
        if ($firstDataObject instanceof \Magento\Sales\Model\Order\Item) {
            foreach (['udropship_vendor'] as $key) {
                $firstDataObject->setData($key, $secondDataObject->getData($key));
            }
        }
        return $next($interfaceName, $firstDataObject, $secondDataObject);
    }
}
