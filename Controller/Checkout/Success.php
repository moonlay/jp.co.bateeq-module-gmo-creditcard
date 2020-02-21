<?php

namespace Moonlay\GMOMultiPayment\Controller\Checkout;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * @package Moonlay\GMOMultiPayment\Controller\Checkout
 */
class Success extends AbstractAction implements HttpPostActionInterface
{
    public function execute()
    {
        $isValid = $this->getCryptoHelper()->isValidSignature($this->getRequest()->getParams(), $this->getGatewayConfig()->getShopID());
        $ErrCode = $this->getRequest()->get("ErrCode");
        $ErrInfo = $this->getRequest()->get("ErrInfo");
        $orderId = $this->getRequest()->get("OrderID");
        $transactionId = $this->getRequest()->get('AccessID');

        if (!$isValid) {
            $this->getLogger()->debug('Possible site forgery detected: invalid response signature.');
            $this->getMessageManager()->addErrorMessage(__("エラー！ 不明な応答。 " . $this->getCustomerSupportEmail() . " にお問い合わせください。")); // payment failed notif
            $this->_redirect('checkout/onepage/failure', array('_secure' => false));
            return;
        }

        if (!$orderId) {
            $this->getLogger()->debug("GMO Multipayment returned a null order id. This may indicate an issue with the GMO Multipayment gateway.");
            $this->getMessageManager()->addErrorMessage(__("注文は見つかりませんでした。" . $this->getCustomerSupportEmail() . " にお問い合わせください。")); // payment failed notif
            $this->_redirect('checkout/onepage/failure', array('_secure' => false));
            return;
        }

        $order = $this->getOrderById($orderId);
        if (!$order) {
            $this->getLogger()->debug("Order ID was not found: #$orderId");
            $this->getMessageManager()->addErrorMessage(__("注文は見つかりませんでした。" . $this->getCustomerSupportEmail() . " にお問い合わせください。")); // payment failed notif
            $this->_redirect('checkout/onepage/failure', array('_secure' => false));
            return;
        }

        $isValidPayment = $this->checkTotalDue((int) $order->getTotalDue(), (int) $this->getRequest()->get("Amount") + (int) $this->getRequest()->get("Tax"));
        if (!$isValidPayment) {
            $this->getLogger()->debug("GMO Multipayment returned response total price was does not match the order id: $orderId");
            $this->getCheckoutHelper()->cancelCurrentOrder("Order #$orderId was rejected by System. Transaction #$transactionId.");
            $this->getMessageManager()->addErrorMessage(__("申し訳ありませんが、お支払いはシステムによって拒否されました。")); // payment failed notif
            $this->_redirect('checkout/onepage/failure', array('_secure' => false));
            return;
        }

        if (empty($ErrCode) && empty($ErrInfo) && $order->getState() === Order::STATE_PROCESSING) {
            $this->_redirect('checkout/onepage/success', array('_secure' => false));
            return;
        }

        if (!empty($ErrCode) && !empty($ErrInfo) && $order->getState() === Order::STATE_CANCELED) {
            $this->getCheckoutHelper()->restoreQuote(); //restore cart
            $this->getMessageManager()->addErrorMessage(__("ご注文はキャンセルされました。再注文してください")); // payment failed notif
            $this->_redirect('checkout/cart', array('_secure' => false));
            return;
        }

        if (empty($ErrCode) && empty($ErrInfo)) {
            $orderState = Order::STATE_PROCESSING;

            $orderStatus = $this->getGatewayConfig()->getApprovedOrderStatus();
            if (!$this->statusExists($orderStatus)) {
                $orderStatus = $order->getConfig()->getStateDefaultStatus($orderState);
            }

            $emailCustomer = $this->getGatewayConfig()->isEmailCustomer();

            // changes order status and state
            $order->setState($orderState)
                ->setStatus($orderStatus)
                ->addStatusHistoryComment("GMO Multipayment authorisation success. Transaction #$orderId")
                ->setIsCustomerNotified($emailCustomer);

            $payment = $order->getPayment();

            // set new transaction
            $payment->setTransactionId(htmlentities($orderId));
            $transaction = $payment->addTransaction(Transaction::TYPE_CAPTURE, null, true);
            $transaction->setAdditionalInformation(Transaction::RAW_DETAILS, array(
                'AccessID' => $transactionId,
                'AccessPass' => $this->getRequest()->get('AccessPass')
            ));
            $transaction->save();
            $order->save();

            // send invoice email
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
            $emailSender->send($order);

            $invoiceAutomatically = $this->getGatewayConfig()->isAutomaticInvoice();
            if ($invoiceAutomatically) {
                $this->invoiceOrder($order, $transactionId);
            }

            $this->getMessageManager()->addSuccessMessage(__("取引は成功しました。")); // payment success notif
            $this->_redirect('checkout/onepage/success', array('_secure' => false));
        } else {
            $this->getCheckoutHelper()->cancelCurrentOrder("Order #$orderId was rejected by GMO Multipayment. Transaction #$transactionId.");
            $this->getCheckoutHelper()->restoreQuote(); //restore cart
            $this->getMessageManager()->addErrorMessage(__("お支払いに問題がありました。後でもう一度やり直してください。")); // payment failed notif
            $this->_redirect('checkout/cart', array('_secure' => false));
        }
    }

    // check order status exists
    private function statusExists($orderStatus)
    {
        $statuses = $this->getObjectManager()
            ->get('Magento\Sales\Model\Order\Status')
            ->getResourceCollection()
            ->getData();
        foreach ($statuses as $status) {
            if ($orderStatus === $status["status"]) return true;
        }
        return false;
    }

    // create new invoice
    private function invoiceOrder($order, $transactionId)
    {
        if (!$order->canInvoice()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Cannot create an invoice.')
            );
        }

        $invoice = $this->getObjectManager()
            ->create('Magento\Sales\Model\Service\InvoiceService')
            ->prepareInvoice($order);

        if (!$invoice->getTotalQty()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('You can\'t create an invoice without products.')
            );
        }

        /*
         * Look Magento/Sales/Model/Order/Invoice.register() for CAPTURE_OFFLINE explanation.
         * Basically, if !config/can_capture and config/is_gateway and CAPTURE_OFFLINE and 
         * Payment.IsTransactionPending => pay (Invoice.STATE = STATE_PAID...)
         */
        $invoice->setTransactionId($transactionId);
        $invoice->setRequestedCaptureCase(Order\Invoice::CAPTURE_OFFLINE);
        $invoice->register();

        $transaction = $this->getObjectManager()->create('Magento\Framework\DB\Transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transaction->save();
    }

    private function checkTotalDue(int $totalPayment, int $responseTotalPayment)
    {
        if ($totalPayment == $responseTotalPayment) return true;

        return false;
    }
}
