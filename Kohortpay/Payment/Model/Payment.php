<?php

namespace Kohortpay\Payment\Model;

/**
 * Pay In Store payment method model
 */
class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
  /**
   * Payment code
   *
   * @var string
   */
  protected $_code = 'kohortpay';

  protected $_isGateway = true;

  protected $_isOffline = false;

  protected $_minAmount = 50;

  /**
   * Authorizes specified amount.
   *
   * @param InfoInterface $payment
   * @param float         $amount
   *
   * @return $this
   *
   * @throws LocalizedException
   */
  public function authorize(
    \Magento\Payment\Model\InfoInterface $payment,
    $amount
  ) {
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
}
