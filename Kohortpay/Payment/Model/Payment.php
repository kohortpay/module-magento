<?php

namespace Kohortpay\Payment\Model;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

use Magento\Framework\Message\ManagerInterface;

use Magento\Payment\Model\InfoInterface;

/**
 * Pay In Store payment method model
 */
class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
  protected $_code = 'kohortpay';

  protected $_isGateway = true;

  protected $_isOffline = false;

  protected $_minAmount = 50;

  protected $_canRefund = true;
  protected $_canRefundInvoicePartial = true;

  protected $_canCapture = true;

  /**
   * @var ManagerInterface
   */
  protected $messageManager;

  /**
   * @var \Magento\Framework\App\Config\ScopeConfigInterface
   */
  protected $scopeConfig;

  /**
   * @var \Magento\Framework\Pricing\PriceCurrencyInterface
   */
  protected $priceCurrency;

  /**
   * @var \Magento\Store\Model\StoreManagerInterface
   */
  protected $storeManager;

  /**
   * Payment constructor.
   *
   * @param \Magento\Framework\Model\Context $context
   * @param \Magento\Framework\Registry $registry
   * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
   * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
   * @param \Magento\Payment\Helper\Data $paymentData
   * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
   * @param \Magento\Payment\Model\Method\Logger $logger
   * @param \Magento\Framework\Module\ModuleListInterface $moduleList
   * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
   * @param array $data
   * @param ManagerInterface $messageManager
   */
  public function __construct(
    \Magento\Framework\Model\Context $context,
    \Magento\Framework\Registry $registry,
    \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
    \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
    \Magento\Payment\Helper\Data $paymentData,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Magento\Payment\Model\Method\Logger $logger,
    ManagerInterface $messageManager,
    \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    array $data = []
  ) {
    parent::__construct(
      $context,
      $registry,
      $extensionFactory,
      $customAttributeFactory,
      $paymentData,
      $scopeConfig,
      $logger,
      null,
      null,
      $data
    );

    $this->scopeConfig = $scopeConfig;
    $this->messageManager = $messageManager;
    $this->priceCurrency = $priceCurrency;
    $this->storeManager = $storeManager;
  }

  /**
   * Capture payment
   *
   * @param InfoInterface $payment
   * @param float         $amount
   *
   * @return $this
   *
   * @throws LocalizedException
   */
  public function capture(InfoInterface $payment, $amount)
  {
    return $this;
  }

  /**
   * Refund specified amount.
   *
   * @param InfoInterface $payment
   * @param float         $amount
   *
   * @return $this
   *
   * @throws LocalizedException
   */
  public function refund(InfoInterface $payment, $amount)
  {
    $this->refundAction($payment, $amount);

    return $this;
  }

  /** Make payment available if order amount is superior to 50$ */
  public function isAvailable(
    \Magento\Quote\Api\Data\CartInterface $quote = null
  ) {
    $minAmount = $this->getConfigData('minimum_order_total');
    $total = $quote->getBaseGrandTotal();

    if ($minAmount !== null && $minAmount > $total) {
      return false;
    }

    if (!$this->getConfigData('merchant_key')) {
      return false;
    }

    return true;
  }

  /**
   * Refund logic
   *
   * @param $payment
   * @param $amount
   */
  private function refundAction($payment, $amount)
  {
    $orderCurrency = $payment->getOrder()->getOrderCurrencyCode();
    if (
      $orderCurrency !== $this->storeManager->getStore()->getBaseCurrencyCode()
    ) {
      $amount = $this->priceCurrency->convert($amount, null, $orderCurrency);
    }

    $client = new Client();

    $merchantKey = $this->scopeConfig->getValue(
      'payment/kohortpay/merchant_key',
      \Magento\Store\Model\ScopeInterface::SCOPE_STORE
    );

    try {
      $response = $client->post('https://api.kohortpay.com/refunds', [
        'headers' => [
          'Authorization' => 'Bearer ' . $merchantKey,
        ],
        'json' => [
          'amount' => number_format($amount, 2, '.', '') * 100,
          'payment_intent_id' => $payment->getParentTransactionId(),
        ],
      ]);

      $this->messageManager->addSuccessMessage(
        __('Payment refunded successfully with KohortPay.')
      );
    } catch (ClientException $e) {
      if ($e->hasResponse()) {
        $errorResponse = json_decode(
          $e
            ->getResponse()
            ->getBody()
            ->getContents(),
          true
        );

        if (isset($errorResponse['error']['message'])) {
          throw new \Magento\Framework\Validator\Exception(
            __($errorResponse['error']['message'])
          );
        }
      }
      throw new \Magento\Framework\Validator\Exception(
        __('Payment refunding error.')
      );
    }
  }
}
