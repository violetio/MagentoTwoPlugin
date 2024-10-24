<?php
namespace Violet\VioletConnect\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;

/**
 * Violet After Shipment Save Event
 *
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 */
class ShipmentSaveAfter implements ObserverInterface
{

    private $logger;
    private $vClient;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Violet\VioletConnect\Helper\Client $vClient
    ) {
        $this->logger = $logger;
        $this->vClient = $vClient;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $shipment = $observer->getEvent()->getShipment();
            $order = $shipment->getOrder();

            // escape if no order
            if ($order == null) return;
            // escape if not a violet order
            $payment = $order->getPayment();
            if ($payment != null && $payment->getMethod() != 'violet') return;

            // notify violet of refund
            $this->vClient->orderShipped($order->getEntityId());
        } catch (\Exception $e) {
            $this->logger->info("Error notifying Violet of shipment: " . $e->getMessage());
        }
    }
}