<?php

namespace Moonlay\GMOCreditCard\Controller\Checkout;

/**
 * @package Moonlay\GMOCreditCard\Controller\Checkout
 */
class Cancel extends AbstractAction {
    
    public function execute() {
        $orderId = $this->getRequest()->get('orderId');
        $order =  $this->getOrderById($orderId);

        if ($order && $order->getId()) {
            $this->getLogger()->debug('Requested order cancellation by customer. OrderId: ' . $order->getIncrementId());
            $this->getCheckoutHelper()->cancelCurrentOrder("GMO Creditcard: ".($order->getId())." was cancelled by the customer.");
            $this->getCheckoutHelper()->restoreQuote(); //restore cart
            $this->getMessageManager()->addWarningMessage(__("You have successfully canceled your GMO Creditcard. Please click on 'Update Shopping Cart'."));
        }
        $this->_redirect('checkout/cart');
    }

}
