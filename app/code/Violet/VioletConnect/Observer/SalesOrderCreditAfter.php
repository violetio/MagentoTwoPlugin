<?php
namespace Violet\VioletConnect\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Violet After Sales Order Credited Event
 *
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 */
class SalesOrderCreditAfter implements ObserverInterface
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
            $creditmemo = $observer->getEvent()->getCreditmemo();
            if ($creditmemo == null) return;
            foreach ($creditmemo->getAllItems() as $item) {
                $this->vClient->productUpdated($item->getSku());
            }
        } catch (\Exception $e) {
            $this->logger->info("Error notifying Violet of quanity update after credit memo: " . $e->getMessage());
        }
    }
}
