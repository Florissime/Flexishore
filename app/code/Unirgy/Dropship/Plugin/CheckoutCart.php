<?php
namespace Unirgy\Dropship\Plugin;

class CheckoutCart
{
    protected $_iHlp;
    public function __construct(
        \Unirgy\Dropship\Helper\Item $itemHelper
    ) {
        $this->_iHlp = $itemHelper;
    }

    public function beforeDispatch(
        \Magento\Checkout\Controller\Cart $subject,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $cartUpdateActions = ['checkout_sidebar_removeItem','checkout_cart_delete','checkout_cart_updatePost','checkout_cart_add'];
        if (in_array($request->getFullActionName(), $cartUpdateActions)) {
            $this->_iHlp->setIsCartUpdateActionFlag(true);
        }
    }
}