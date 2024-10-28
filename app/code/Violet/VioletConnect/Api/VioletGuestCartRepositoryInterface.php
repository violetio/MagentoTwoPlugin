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
     * Place an order for a specified cart.
     *
     * @param string $cartId The cart ID.
     * @param PaymentInterface|null $paymentMethod
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return int Order ID.
     */
    public function placeOrder($cartId, PaymentInterface $paymentMethod = null);

    /**
     * Deletes a Guest Cart
     *
     * @param string $cartId The cart ID.
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return int Cart ID.
     */
    public function deleteCart($cartId);
}