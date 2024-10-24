<?php
namespace Violet\VioletConnect\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

/**
 * Violet After Order Placed
 *
 * @copyright  2019 Violet.io, Inc.
 * @since      1.0.3
 */
class SalesOrderPlaced implements ObserverInterface
{
    private $objectManager;
    private $vClient;

    public function __construct(
      \Magento\Framework\ObjectManagerInterface $objectManager,
      \Violet\VioletConnect\Helper\Client $vClient
    ) {
            $this->objectManager = $objectManager;
            $this->vClient = $vClient;
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

            $orderItems = $order->getAllItems();
            if ($orderItems !== null) {
                foreach ($orderItems as $item) {
                    if ($item !== null && $item->getTypeId() != "configurable") {
                        $this->vClient->productUpdated($item->getSku());
                    }
                }
          }

        } catch (\Exception $e) {
        }
    }
}
