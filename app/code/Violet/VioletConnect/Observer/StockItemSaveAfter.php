<?php
namespace Violet\VioletConnect\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Violet After Item Save Event
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 */
class StockItemSaveAfter implements ObserverInterface
{

    private $logger;
    private $vClient;
    private $productRepository;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Violet\VioletConnect\Helper\Client $vClient,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->logger = $logger;
        $this->vClient = $vClient;
        $this->productRepository = $productRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $item = $observer->getEvent()->getItem();

            if ($item !== null && $item->getTypeId() != "configurable") {
                $product = $this->productRepository->getById($item->getitemId());
                if ($product !== null && $product->hasData('quantity_and_stock_status')) {
                    $qty = $product->getData('quantity_and_stock_status')['qty'];
                    $this->vClient->qtyUpdated($product->getSku(), $qty);
                }
            }
        } catch (\Exception $e) {
            $this->logger->info("Error notifying Violet of quanity update.");
        }
    }
}
