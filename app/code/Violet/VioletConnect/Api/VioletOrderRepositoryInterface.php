<?php
namespace Violet\VioletConnect\Api;

use Violet\VioletConnect\Model\Data\VioletCart;

/**
 * Interface VioletOrderRepositoryInterface
 */
interface VioletOrderRepositoryInterface
{
    /**
     * Calculate a cart
     *
     * @param VioletCart $violetCart
     * @return int
     */
    public function createOrder(\Violet\VioletConnect\Model\Data\VioletCart $violetCart);

    /**
     * Create an optionally initialized cart
     *
     * @param VioletCart $violetCart
     * @throws \Magento\Framework\Exception\CouldNotSaveException The cart and quote could not be created.
     * @return string Cart ID.
     */
    public function createInitializedCart(\Violet\VioletConnect\Model\Data\VioletCart $violetCart);
}
