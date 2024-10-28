<?php
namespace Violet\VioletConnect\Model\ResourceModel;

use Violet\VioletConnect\Api\VioletGuestCartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Violet VioletGuestCartRepository
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2024 Violet.io, Inc.
 * @since      1.2.0
 */
class VioletGuestCartRepository implements VioletGuestCartRepositoryInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;
     /**
     * @var QuoteManagement
     */
    private $quoteManagement;
    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;


    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param QuoteManagement $quoteManagement
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteManagement = $quoteManagement;
    }

    /**
     * @api
     * @param string $cartId The cart ID.
     * @return int incrementId
     */
    public function placeOrder($cartId, \Magento\Quote\Api\Data\PaymentInterface $paymentMethod = null)
    {
        if (!$cartId) {
            throw new InputException(
                __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'quoteId'])
            );
        }

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        // load the quote using the unmasked ID
        $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
      
        // any usage of this endpoint must use the violet payment method
        $quote->setPaymentMethod('violet'); //payment method
        $quote->getPayment()->importData(['method' => 'violet']);
        $quote->save();
       
        return $this->quoteManagement->placeOrder($quoteIdMask->getQuoteId(), $paymentMethod);
    }

    /**
     * @api
     * @param string $cartId The cart ID.
     * @return int cartId
     */
    public function deleteCart($cartId)
    {
        if (!$cartId) {
            throw new InputException(
                __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'quoteId'])
            );
        }

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        // load the quote using the unmasked ID
        $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());

        // delete the quote
        $quote->delete($quote);

        return $cartId;
    }

}