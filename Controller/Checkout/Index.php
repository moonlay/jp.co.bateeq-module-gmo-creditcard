<?php

namespace Moonlay\GMOMultiPayment\Controller\Checkout;

use Magento\FunctionalTestingFramework\Suite\Util\SuiteObjectExtractor;
use Magento\Sales\Model\Order;

/**
 * @package Moonlay\GMOMultiPayment\Controller\Checkout
 */
class Index extends AbstractAction
{
    private function getGMOData($order)
    {
        if ($order == null) {
            $this->getLogger()->debug('Unable to get order from last lodged order id. Possibly related to a failed database call');
            $this->_redirect('checkout/onepage/error', array('_secure' => false));
        }

        $orderId = $order->getRealOrderId();
        $customerId = $order->load($orderId)->getCustomerId();

        $ShopPassString  =  $this->getCryptoHelper()->getEncriptPassString(
            array(
                $this->getGatewayConfig()->getShopID(),
                $orderId,
                strval($order->getTotalDue() - $order->getShippingAmount()),
                (int) $order->getShippingAmount(),
                $this->getGatewayConfig()->getShopPassword(),
                date('YmdHis', strtotime($order->getCreatedAt()))
            )
        );

        $memberPassString = $this->getCryptoHelper()->getEncriptPassString(
            array(
                $this->getGatewayConfig()->getSiteID(),
                null,
                $this->getGatewayConfig()->getSitePassword(),
                date('YmdHis', strtotime($order->getCreatedAt())),
            )
        );

        $data = array(
            'ShopPassString' => $ShopPassString,
            'ShopID' => $this->getGatewayConfig()->getShopID(),
            'OrderID' => $orderId,
            'Amount' => strval((int) $order->getTotalDue() - (int) $order->getShippingAmount()),
            'Tax' => (int) $order->getShippingAmount(),
            'DateTime'  => date('YmdHis', strtotime($order->getCreatedAt())),
            'RetURL' => $this->getDataHelper()->getCompleteUrl(),
            'CancelURL' => $this->getDataHelper()->getCancelledUrl($orderId),
            'ClientField1' => null,
            'ClientField2' => null,
            'ClientField3' => null,
            'UserInfo' => 'pc',
            'RetryMax' => '10',
            'SessionTimeout' => '600',
            'Enc' => 'utf-8',
            'Lang' => 'ja',
            'Confirm' => '1',
            'UseCredit' => '1',
            'UseCvs' => '0',
            'UseEdy' => '0',
            'UseSuica' => '0',
            'UsePayEasy' => '0',
            'UsePayPal'  => '0',
            'UseNetid' => '0',
            'UseWebMoney' => '0',
            'UseAu' => '0',
            'UseDocomo' => '0',
            'UseSb' => '0',
            'UseJibun' => '0',
            'UseJcbPreca' => '0',
            'TemplateNo' => '1',
            'JobCd' => 'CAPTURE',
            'ItemCode' => null,
            'SiteID' => $this->getGatewayConfig()->getSiteID(),
            'MemberID' => null,
            'MemberPassString' => $memberPassString,
            'ReserveNo' => $orderId,
            'MemberNo' => $customerId,
            'RegisterDisp1' => null,
            'RegisterDisp2' => null,
            'RegisterDisp3' => null,
            'RegisterDisp4' => null,
            'RegisterDisp5' => null,
            'RegisterDisp6' => null,
            'RegisterDisp7' => null,
            'RegisterDisp8' => null,
            'ReceiptsDisp1' => null,
            'ReceiptsDisp2' => null,
            'ReceiptsDisp3' => null,
            'ReceiptsDisp4' => null,
            'ReceiptsDisp5' => null,
            'ReceiptsDisp6' => null,
            'ReceiptsDisp7' => null,
            'ReceiptsDisp8' => null,
            'ReceiptsDisp9' => null,
            'ReceiptsDisp10' => null,
            'ReceiptsDisp11' => null,
            'ReceiptsDisp12' => null,
            'ReceiptsDisp13' => null,
            'EdyAddInfo1' => null,
            'EdyAddInfo2' => null,
            'ItemName' => null,
            'SuicaAddInfo1' => null,
            'SuicaAddInfo2' => null,
            'SuicaAddInfo3' => null,
            'SuicaAddInfo4' => null,
            'Commodity' => null,
            'ServiceName' => null,
            'ServiceTel' => null,
            'DocomoDisp1' => null,
            'DocomoDisp2' => null,
            'PaymentTermSec' => null,
            'PayDescription'  => null,
            'CarryInfo' => null
        );
        return $data;
    }

    private function postToCheckout($checkoutUrl, $payload)
    {
        echo '<form id="form" action=' . $checkoutUrl . ' method="post">';
        foreach ($payload as $key => $value) {
            echo '<input type="hidden" id="'.$key.'" name="'.$key.'" value="' . htmlspecialchars($value, ENT_QUOTES) . '"/>';
        }

        echo '</form>
            <script>
                document.getElementById("form").submit();
            </script>';
    }

    /**
     * 
     *
     * @return void
     */
    public function execute()
    {
        try {
            $order = $this->getOrder();
            if ($order->getState() === Order::STATE_PENDING_PAYMENT) {
                // $payload = $this->getPayload($order);
                $payload = $this->getGMOData($order);
                $this->postToCheckout($this->getDataHelper()->getCheckoutUrl(), $payload);
            } else if ($order->getState() === Order::STATE_CANCELED) {
                $errorMessage = $this->getCheckoutSession()->getErrorMessage(); //set in InitializationRequest
                if ($errorMessage) {
                    $this->getMessageManager()->addWarningMessage($errorMessage);
                    $errorMessage = $this->getCheckoutSession()->unsErrorMessage();
                }
                $this->getCheckoutHelper()->restoreQuote(); //restore cart
                $this->_redirect('checkout/cart');
            } else {
                $this->getLogger()->debug('Order in unrecognized state: ' . $order->getState());
                $this->_redirect('checkout/cart');
            }
        } catch (Exception $ex) {
            $this->getLogger()->debug('An exception was encountered in GMOMultiPayment/checkout/index: ' . $ex->getMessage());
            $this->getLogger()->debug($ex->getTraceAsString());
            $this->getMessageManager()->addErrorMessage(__('Unable to start gmo multipayment Checkout.'));
        }
    }
}
