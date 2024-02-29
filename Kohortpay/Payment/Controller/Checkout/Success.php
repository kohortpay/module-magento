<?php
namespace Kohortpay\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Framework\DB\Transaction;

class Success extends Action
{
  /** @var Session */
  private $checkoutSession;

  /**
   * @var InvoiceService
   */
  protected $invoiceService;

  /**
   * @var InvoiceSender
   */
  protected $invoiceSender;

  /**
   * @var Transaction
   */
  protected $transaction;

  /**
   * @param Context $context
   * @param Session $checkoutSession
   * @param InvoiceService $invoiceService
   * @param InvoiceSender $invoiceSender
   */
  public function __construct(
    Context $context,
    Session $checkoutSession,
    InvoiceService $invoiceService,
    InvoiceSender $invoiceSender,
    Transaction $transaction
  ) {
    parent::__construct($context);
    $this->checkoutSession = $checkoutSession;
    $this->invoiceService = $invoiceService;
    $this->invoiceSender = $invoiceSender;
    $this->transaction = $transaction;
  }

  /**
   * Initialize redirect to bank
   *
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute()
  {
    $order = $this->checkoutSession->getLastRealOrder();
    $paymentId = $this->getRequest()->getParam('payment_id');

    // Generate invoice
    if ($order->canInvoice()) {
      $payment = $order->getPayment();
      $payment->setParentTransactionId(null);

      $payment->setTransactionId($paymentId)->setIsTransactionClosed(0);

      $invoice = $this->invoiceService->prepareInvoice($order);
      $invoice->setRequestedCaptureCase(
        \Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE
      );
      $invoice->register();
      $invoice->save();

      $transactionSave = $this->transaction
        ->addObject($invoice)
        ->addObject($invoice->getOrder());

      $transactionSave->save();
      $this->invoiceSender->send($invoice);
      $order
        ->addStatusHistoryComment(
          __('Notified customer about invoice #%1.', $invoice->getId())
        )
        ->setIsCustomerNotified(true)
        ->save();
    }

    $this->_redirect('checkout/onepage/success');
  }
}
