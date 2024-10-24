<?php
namespace Violet\VioletConnect\Api;

/**
 * Interface WebServiceRepositoryInterface
 */
interface VioletRepositoryInterface
{
    /**
     *
     * @return int
     */
    public function skuCount();

    /**
     * 
     * @param int $page
     * @param int $pageSize
     * @return Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function skus($page, $pageSize);

    /**
     * 
     * @param string $sku
     * @return Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function skuChildren($sku);

    /**
     * 
     * @param string $sku
     * @return Magento\Catalog\Api\Data\ProductInterface
     */
    public function skuParent($sku);

    /**
     * 
     * @param int $orderId
     * @return Magento\Sales\Api\Data\ShipmentInterface[]
     */
    public function orderShipments($orderId);

     /**
     * 
     * @return Violet\VioletConnect\Model\Data\StoreAdmin
     */
    public function storeAdmin();

    /**
     * 
     * @param Violet\VioletConnect\Model\Data\VioletConfiguration $configuration
     * @return Violet\VioletConnect\Model\Data\VioletConfiguration
     */
    public function violetConfiguration($configuration);

    /**
     * 
     * @return Violet\VioletConnect\Model\Data\VioletConfiguration
     */
    public function getVioletConfiguration();
}
