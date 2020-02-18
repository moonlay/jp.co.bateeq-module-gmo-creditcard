<?php

namespace Moonlay\GMOMultiPayment\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Moonlay\GMOMultiPayment\Helper\Crypto;
use Moonlay\GMOMultiPayment\Helper\Data;
use Moonlay\GMOMultiPayment\Helper\Checkout;
use Moonlay\GMOMultiPayment\Gateway\Config\Config;
use Psr\Log\LoggerInterface;

abstract class AbstractAction extends Action {

    const LOG_FILE = 'gmo_multipayment.log';
    const GMO_MULTIPAYMENT_DEFAULT_CURRENCY_CODE = 'JPY';
    const GMO_MULTIPAYMENT_DEFAULT_COUNTRY_CODE = 'JP';

    private $_context;

    private $_checkoutSession;

    private $_orderFactory;

    private $_cryptoHelper;

    private $_dataHelper;

    private $_checkoutHelper;

    private $_scopeConfig;

    private $_gatewayConfig;

    private $_messageManager;

    private $_logger;

    public function __construct(
        Config $gatewayConfig,
        Session $checkoutSession,
        Context $context,
        OrderFactory $orderFactory,
        Crypto $cryptoHelper,
        ScopeConfigInterface $scopeConfigInterface,
        Data $dataHelper,
        Checkout $checkoutHelper,
        LoggerInterface $logger) {
        parent::__construct($context);
        $this->_gatewayConfig = $gatewayConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_messageManager = $context->getMessageManager();
        $this->_orderFactory = $orderFactory;
        $this->_scopeConfig = $scopeConfigInterface;
        $this->_cryptoHelper = $cryptoHelper;
        $this->_dataHelper = $dataHelper;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_logger = $logger;
    }
    
    protected function getContext() {
        return $this->_context;
    }

    protected function getCheckoutSession() {
        return $this->_checkoutSession;
    }

    protected function getOrderFactory() {
        return $this->_orderFactory;
    }

    protected function getCryptoHelper() {
        return $this->_cryptoHelper;
    }

    protected function getDataHelper() {
        return $this->_dataHelper;
    }

    protected function getCheckoutHelper() {
        return $this->_checkoutHelper;
    }

    protected function getScopeConfig() {
        return $this->_scopeConfig;
    }

    protected function getGatewayConfig() {
        return $this->_gatewayConfig;
    }

    protected function getMessageManager() {
        return $this->_messageManager;
    }

    protected function getLogger() {
        return $this->_logger;
    }
    
    protected function getOrder()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();

        if (!isset($orderId)) {
            return null;
        }

        return $this->getOrderById($orderId);
    }

    protected function getOrderById($orderId)
    {
        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);

        if (!$order->getId()) {
            return null;
        }

        return $order;
    }

    protected function getObjectManager()
    {
        return \Magento\Framework\App\ObjectManager::getInstance();
    }

    protected function getCustomerSupportEmail() {
        return $this->getScopeConfig()->getValue('trans_email/ident_support/email', ScopeInterface::SCOPE_STORE);
    }

}
