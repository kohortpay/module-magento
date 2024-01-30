<?php
namespace Kohortpay\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Quote\Model\QuoteFactory;

class Cancel extends Action
{
  /** @var Session */
  private $checkoutSession;

  /**
   * @var OrderManagementInterface
   */
  protected $_order;

  /**
   * @var QuoteFactory
   */
  protected $quoteFactory;

  /**
   * @param Context $context
   * @param Session $checkoutSession
   * @param OrderManagementInterface $orderManagementInterface
   */
  public function __construct(
    Context $context,
    Session $checkoutSession,
    QuoteFactory $quoteFactory,
    OrderManagementInterface $orderManagementInterface
  ) {
    parent::__construct($context);
    $this->checkoutSession = $checkoutSession;
    $this->_order = $orderManagementInterface;
    $this->quoteFactory = $quoteFactory;
  }

  /**
   * Initialize redirect to bank
   *
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute()
  {
    $order = $this->checkoutSession->getLastRealOrder();

    if ($order->canCancel()) {
      $order->addStatusHistoryComment(
        __('KohortPay Payment has been canceled by the customer.')
      );
      $order->save();
      $this->_order->cancel($order->getId());
    }

    // Restore quote
    $id = $this->checkoutSession->getLastQuoteId();
    $quote = $this->quoteFactory->create()->loadByIdWithoutStore($id);
    if ($quote->getId()) {
      $quote
        ->setIsActive(true)
        ->setReservedOrderId(null)
        ->save();
      $this->checkoutSession->replaceQuote($quote);
    }

    $this->_redirect('checkout');
  }
}
