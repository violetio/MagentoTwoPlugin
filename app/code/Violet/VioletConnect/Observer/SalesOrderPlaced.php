<?php
namespace Violet\VioletConnect\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

/**
 * Violet After Order Placed
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2019 Violet.io, Inc.
 * @since      1.0.3
 */
class SalesOrderPlaced implements ObserverInterface
{
    private $objectManager;

    public function __construct(
      \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
            $this->objectManager = $objectManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $order = $observer->getOrder();
            if ($order == null) return;

            $payment = $order->getPayment();

            if ($payment != null && $payment->getMethod() == 'violet') {

              $invoice = $this->objectManager->create('Magento\Sales\Model\Service\InvoiceService')
              ->prepareInvoice($order);// Register as invoice item

              $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
              $invoice->register();

              $order->setTotalPaid($order->getGrandTotal())
              ->save();
            }

        } catch (\Exception $e) {
        }
    }
}
