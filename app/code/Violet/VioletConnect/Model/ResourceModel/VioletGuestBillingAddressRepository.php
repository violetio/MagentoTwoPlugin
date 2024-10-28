<?php
namespace Violet\VioletConnect\Model\ResourceModel;

use Violet\VioletConnect\Api\VioletGuestBillingAddressRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Api\BillingAddressManagementInterface;

/**
 * Violet VioletGuestBillingAddressRepository
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2024 Violet.io, Inc.
 * @since      1.2.0
 */
class VioletGuestBillingAddressRepository implements VioletGuestBillingAddressRepositoryInterface
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
     * @var BillingAddressManagementInterface
     */
    private $billingAddressManagement;
    /**
     * @var QuoteAddressValidator
     */
    protected $addressValidator;
   

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param BillingAddressManagementInterface $billingAddressManagement
     * @param QuoteAddressValidator $addressValidator Address validator.
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement,
        \Magento\Quote\Model\QuoteAddressValidator $addressValidator,
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->billingAddressManagement = $billingAddressManagement;
        $this->addressValidator = $addressValidator;
    }

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
    public function assign($cartId, \Magento\Quote\Api\Data\AddressInterface $address, $useForShipping = false) {
        if (!$cartId) {
            throw new InputException(
                __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'quoteId'])
            );
        }

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        // load the quote using the unmasked ID
        $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());

        // extract the items before saving the guest cart
        $items = $quote->getItems();

         // validate the address
         $this->addressValidator->validateWithExistingAddress($quote, $address);

        // apply billing address to quote
        $quote->setBillingAddress($address);

        // if requested apply billing address as shipping address
        if ($useForShipping) {
            $quote->setShippingAddress($address);  
        }

        $quote->save();

        // reinforce the custom prices
        if ($items !== null) {
            foreach($items as $item) {
                $item->setCustomPrice($item->getPrice());
                $item->setOriginalCustomPrice($item->getPrice());
                $item->getProduct()->setIsSuperMode(true);
                $item->save();
            }
        }

        // // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        return $quote->getBillingAddress()->getId();
    }
}