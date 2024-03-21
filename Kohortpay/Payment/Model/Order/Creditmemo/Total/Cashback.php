<?php
namespace Kohortpay\Payment\Model\Order\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client;

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

    $client = new Client();
    $merchantKey = \Magento\Framework\App\ObjectManager::getInstance()
      ->get('Magento\Framework\App\Config\ScopeConfigInterface')
      ->getValue('payment/kohortpay/merchant_key');
    $transactionId = $order->getPayment()->getLastTransId();
    $cashback = 0;

    try {
      $response = $client->get(
        'https://api.kohortpay.com/payment-intents/' . $transactionId,
        [
          'headers' => [
            'Authorization' => 'Bearer ' . $merchantKey,
          ],
        ]
      );

      $paymentIntent = json_decode($response->getBody()->getContents(), true);
      if (isset($paymentIntent['amount_cashback'])) {
        $cashback = $paymentIntent['amount_cashback'] / 100;
      }
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
          var_dump($errorResponse['error']['message']); // TODO: Replace by error message notification
        }
      }
    }

    $creditmemo->setBaseAdjustmentNegative($cashback);
    $creditmemo->setGrandTotal($creditmemo->getGrandTotal() - $cashback);
    $creditmemo->setBaseGrandTotal(
      $creditmemo->getBaseGrandTotal() - $cashback
    );

    return $this;
  }
}
