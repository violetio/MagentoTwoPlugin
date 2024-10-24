<?php
namespace Violet\VioletConnect\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Violet After Product Save Event
 *
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 */
class ProductSaveAfter implements ObserverInterface
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
     * Observe Product Update
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try{
            // product being updated
            $mageProduct = $observer->getProduct();

            // object manager
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $productId = $mageProduct->getSku();

            // handle hidden product
            if ($mageProduct->getTypeId() == "simple" && $mageProduct->getVisibility() == 1) {
                // collect parent ids
                $parentProductIds = $objectManager
                ->create('Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable')
                ->getParentIdsByChild($mageProduct->getId());

                // if parents exist
                if (!empty($parentProductIds)) {
                    $parentProduct = $objectManager
                    ->create('Magento\Catalog\Model\Product')->load($parentProductIds[0]);

                    $productId = $parentProduct->getSku();
                }
            }

            $this->vClient->productUpdated($productId);

        } catch (\Exception $e) {
            $this->logger->info("Error notifying Violet of product update: " . $e->getMessage());
        }
    }
}
