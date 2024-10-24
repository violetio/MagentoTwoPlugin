<?php
namespace Violet\VioletConnect\Api;

use Violet\VioletConnect\Model\Data\VioletCart;
use Violet\VioletConnect\Model\Data\VioletCalculatedCart;

/**
 * Interface VioletGuestCartTotalRepositoryInterface
 */
interface VioletGuestCartTotalRepositoryInterface
{
    /**
     * Return quote totals data for a specified cart.
     *
     * @param string $cartId The cart ID.
     * @return \Magento\Quote\Api\Data\TotalsInterface Quote totals data.
     */
    public function get($cartId);

    /**
     * Calculate a cart
     *
     * @param VioletCart $violetCart
     * @return VioletCalculatedCart
     */
    public function calculate(\Violet\VioletConnect\Model\Data\VioletCart $violetCart);
}
