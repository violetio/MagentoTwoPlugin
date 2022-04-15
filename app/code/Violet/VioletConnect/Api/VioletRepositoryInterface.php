<?php
namespace Violet\VioletConnect\Api;

/**
 * Interface WebServiceRepositoryInterface
 * @package Violet\VioletConnect\Api
 */
interface VioletRepositoryInterface
{
    /**
     * @param string $token
     * @param int $merchantId
     *
     * @return Violet\VioletConnect\Model\Data\TokenValidation
     */
    public function validateAccount($token, $merchantId);

    /**
     *
     * @return int
     */
    public function skuCount();

    /**
     * @param int $page
     * @param int $pageSize
     *
     * @return Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function skus($page, $pageSize);

    /**
     * @param string $sku
     *
     * @return Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function skuChildren($sku);
}
