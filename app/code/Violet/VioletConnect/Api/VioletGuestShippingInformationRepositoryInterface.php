<?php
namespace Violet\VioletConnect\Api;

/**
 * Interface VioletGuestShippingInformationRepositoryInterface
 */
interface VioletGuestShippingInformationRepositoryInterface
{
   /**
     * @param string $cartId
     * @param \Violet\VioletConnect\Model\Data\VioletShippingInformation $addressInformation
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function saveAddressInformation(
        $cartId,
        \Violet\VioletConnect\Model\Data\VioletShippingInformation $addressInformation
    ); 
}
