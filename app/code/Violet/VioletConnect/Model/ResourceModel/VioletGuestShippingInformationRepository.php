<?php
namespace Violet\VioletConnect\Model\ResourceModel;

use Violet\VioletConnect\Api\VioletGuestShippingInformationRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;


/**
 * Violet VioletGuestShippingInformationRepository
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2024 Violet.io, Inc.
 * @since      1.2.0
 */
class VioletGuestShippingInformationRepository implements VioletGuestShippingInformationRepositoryInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;


    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
        
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * @api
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function saveAddressInformation(
        $cartId, 
        \Violet\VioletConnect\Model\Data\VioletShippingInformation $addressInformation
    ) {
        if (!$cartId) {
            throw new InputException(
                __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'quoteId'])
            );
        }

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        // load the quote using the unmasked ID
        $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());

        $items = $quote->getItems();

        $address = $addressInformation->getShippingAddress();

        $quote->setShippingAddress($address);

        $quote->save();

        if ($items !== null) {
            foreach($items as $item) {
                $item->setCustomPrice($item->getPrice());
                $item->setOriginalCustomPrice($item->getPrice());
                $item->getProduct()->setIsSuperMode(true);
                $item->save();
            }
        }

        $carrierCode = $addressInformation->getShippingCarrierCode();
        $address->setLimitCarrier($carrierCode);
        $methodCode = $addressInformation->getShippingMethodCode();
        $quote->setIsMultiShipping(false);

        $shippingAddress=$quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod($carrierCode . '_' . $methodCode); //shipping method
        $quote->setPaymentMethod('violet'); //payment method

        // compose and apply custom shipping info if any exists
        $quote->setExtShippingInfo($this->composeExtShippingInfo($addressInformation));

        $quote->save();

        // // Collect Totals & Save Quote
        $quote->collectTotals()->save();

         return $quote;
    }


    /**
     * @param VioletShippingInformation $addressInformation
     */
    private function composeExtShippingInfo(\Violet\VioletConnect\Model\Data\VioletShippingInformation $addressInformation) {
        $_hasShippingPrice = $this->addressHasShippingPrice($addressInformation);
        $_hasShippingDescription = $this->addressHasShippingDescription($addressInformation);

        // if no price or description then ext shipping info is null
        if (!$_hasShippingPrice && !$_hasShippingDescription) return null;

        $_customShippingInfo = array();

        if ($_hasShippingPrice) {
            $_customShippingInfo["shipping_price"] = $addressInformation->getShippingMethodPrice();
        }
        if ($_hasShippingDescription) {
            $_customShippingInfo["shipping_description"] = $addressInformation->getShippingMethodDescription();
        }

        return json_encode($_customShippingInfo);
    }

     /**
     * @return boolean
     */
    private function addressHasShippingPrice(\Violet\VioletConnect\Model\Data\VioletShippingInformation $addressInformation) {
        return ($addressInformation->getShippingMethodPrice() !== null && is_numeric($addressInformation->getShippingMethodPrice()));
    }

      /**
     * @return boolean
     */
    private function addressHasShippingDescription(\Violet\VioletConnect\Model\Data\VioletShippingInformation $addressInformation) {
        return ($addressInformation->getShippingMethodDescription() !== null && (strlen(trim($addressInformation->getShippingMethodDescription())) > 0));
    }
}