<?php
namespace Violet\VioletConnect\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Violet After Order Refunded
 *
 * @copyright  2022 Violet.io, Inc.
 * @since      1.1.0
 */
class SalesOrderRefunded implements ObserverInterface
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
            $this->vClient->orderRefunded($observer->getCreditmemo()->getOrderId());
        } catch (\Exception $e) {
          $this->logger->info("Error notifying Violet of refund: " . $e->getMessage());
        }
    }
}
