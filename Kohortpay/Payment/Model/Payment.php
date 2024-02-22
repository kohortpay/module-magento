<?php

namespace Kohortpay\Payment\Model;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

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

  /**
   * @var ManagerInterface
   */
  protected $messageManager;

  /**
   * @var ScopeConfigInterface
   */
  protected $scopeConfig;

  /**
   * Payment constructor.
   *
   * @param ManagerInterface $messageManager
   * @param ScopeConfigInterface $scopeConfig
   */
  public function __construct(
    ManagerInterface $messageManager,
    ScopeConfigInterface $scopeConfig
  ) {
    $this->messageManager = $messageManager;
    $this->scopeConfig = $scopeConfig;
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
  public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
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
        'json' => {
          "amount": $amount * 100,
          "payment_intent_id": "pi_9da8e9439f1437",
          "customer_id": "cus_941695de245ea4"
        },
      ]);

      $this->messageManager->addSuccessMessage(
        __('Payment refunded successfully.')
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
          var_dump($errorResponse['error']['message']);
        }
      }
      throw new \Magento\Framework\Validator\Exception(
        __('Payment refunding error.')
      );
    }
  }
}
