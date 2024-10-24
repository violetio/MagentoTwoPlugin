<?php
namespace Violet\VioletConnect\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Violet Sales Order Item Canceled Event
 *
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 */
class SalesOrderCanceled implements ObserverInterface
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
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $order = $observer->getEvent()->getOrder();
            if ($order == null) return;

            foreach ($order->getAllItems() as $item) {
                if ($item !== null && $item->getTypeId() != "configurable") {
                    $this->vClient->productUpdated($item->getSku());
                }
            }
        } catch (\Exception $e) {
            $this->logger->info("Error in Sales Order Item Canceled: " . $e->getMessage());
        }
    }
}
