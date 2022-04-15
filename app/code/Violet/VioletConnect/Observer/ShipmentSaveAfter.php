<?php
namespace Violet\VioletConnect\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Violet After Shipment Save Event
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 */
class ShipmentSaveAfter implements ObserverInterface
{

    private $logger;
    private $vClient;
    private $trackCollection;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Violet\VioletConnect\Helper\Client $vClient,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection $trackCollection
    ) {
        $this->logger = $logger;
        $this->vClient = $vClient;
        $this->trackCollection = $trackCollection;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $event = $observer->getEvent();
            $shipment = $event->getShipment();
            $order = $shipment->getOrder();
            if ($order == null) return;
            $trackingId = null;
            $carrierName = null;

            $shipmentTracksCollection = $shipment->getTracksCollection()
            ->setPageSize(1, 1);

            if (!empty($shipmentTracksCollection)) {
                $track = $shipmentTracksCollection->getLastItem();
                $trackingId = $track->getTrackNumber();
                $carrierName = $track->getTitle();
            }

            foreach ($order->getAllItems() as $item) {
              $product = $item->getProduct();
              $qty = $product->getQuantityAndStockStatus()['qty'];
              $this->vClient->qtyUpdated($product->getSku(), $qty);
            }

            $orderId = $order->getEntityId();

            $this->vClient->orderShipped($orderId, $trackingId, $carrierName);
        } catch (\Exception $e) {
          $this->logger->info("Error notifying Violet of shipment: " . $e->getMessage());
        }
    }
}
