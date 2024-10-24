<?php
namespace Violet\VioletConnect\Api;

use Magento\Quote\Api\Data\PaymentInterface;
use Violet\VioletConnect\Model\Data\VioletCart;

/**
 * Interface VioletGuestCartRepositoryInterface
 */
interface VioletGuestCartRepositoryInterface
{
    /**
     * Create an optionally initialized cart
     *
     * @param VioletCart $violetCart
     * @throws \Magento\Framework\Exception\CouldNotSaveException The cart and quote could not be created.
     * @return string Cart ID.
     */
    public function createInitializedCart(\Violet\VioletConnect\Model\Data\VioletCart $violetCart);

    /**
     * Place an order for a specified cart.
     *
     * @param string $cartId The cart ID.
     * @param PaymentInterface|null $paymentMethod
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return int Order ID.
     */
    public function placeOrder($cartId, PaymentInterface $paymentMethod = null);
}
