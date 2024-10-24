<?php
namespace Violet\VioletConnect\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Violet After Product Delete Event
 *
 * @copyright  2022 Violet.io, Inc.
 * @since      1.1.0
 */
class ProductDeleteAfter implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Client
     */
    private $vClient;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Violet\VioletConnect\Helper\Client $violetClient
    ) {
        $this->logger = $logger;
        $this->vClient = $violetClient;
    }

    /**
     * Observe Product Deletion
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try{
            // product being updated
            $mageProduct = $observer->getEvent()->getProduct();
            $this->vClient->productDeleted($mageProduct->getSku());

        } catch (\Exception $e) {
            $this->logger->info("Error notifying Violet of product deletion: " . $e->getMessage());
        }
    }
}
