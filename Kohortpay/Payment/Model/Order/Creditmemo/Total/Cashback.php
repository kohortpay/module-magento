<?php
namespace Kohortpay\Payment\Model\Order\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Order creditmemo cashback total calculation model
 */
class Cashback extends AbstractTotal
{
  /**
   * Collects credit memo cashback totals.
   *
   * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
   * @return $this
   * @throws \Magento\Framework\Exception\LocalizedException
   */
  public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
  {
    $order = $creditmemo->getOrder();

    if ($order->getPayment()->getMethod() != 'kohortpay') {
      return $this;
    }

    if ($creditmemo->hasBaseAdjustmentNegative()) {
      return $this;
    }

    // get order payment transaction id
    $transactionId = $order->getPayment()->getLastTransId();

    $cashback = 4.52;

    $creditmemo->setBaseAdjustmentNegative($cashback);
    $creditmemo->setGrandTotal($creditmemo->getGrandTotal() - $cashback);
    $creditmemo->setBaseGrandTotal(
      $creditmemo->getBaseGrandTotal() - $cashback
    );

    return $this;
  }
}
