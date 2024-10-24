<?php
namespace Violet\VioletConnect\Api;

/**
 * Interface VioletGuestBillingAddressRepositoryInterface
 */
interface VioletGuestBillingAddressRepositoryInterface
{
    /**
     * Assign a specified billing address to a specified guest cart.
     *
     * @param string $cartId The cart ID.
     * @param \Magento\Quote\Api\Data\AddressInterface $address Billing address data.
     * @param bool $useForShipping
     * @return int Address ID.
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified guest cart does not exist.
     * @throws \Magento\Framework\Exception\InputException The specified cart ID or address data is not valid.
     */
    public function assign($cartId, \Magento\Quote\Api\Data\AddressInterface $address, $useForShipping = false);
}
