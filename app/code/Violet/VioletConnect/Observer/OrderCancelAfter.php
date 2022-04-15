<?php
namespace Violet\VioletConnect\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Violet After Order Cancel Event
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 */
class OrderCancelAfter implements ObserverInterface
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
            $order = $observer->getOrder();
            if ($order == null) return;

            $orderId = $order->getEntityId();

            $this->vClient->orderCanceled($orderId);
        } catch (\Exception $e) {
            $this->logger->info("Error notifying Violet of order cancelation.");
        }
    }
}
