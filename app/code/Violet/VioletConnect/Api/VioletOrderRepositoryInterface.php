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
}
