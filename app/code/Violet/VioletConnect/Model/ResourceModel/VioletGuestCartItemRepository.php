<?php
namespace Violet\VioletConnect\Model\ResourceModel;

use Violet\VioletConnect\Api\VioletGuestCartItemRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;

/**
 * Violet VioletGuestCartItemRepository
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2024 Violet.io, Inc.
 * @since      1.2.0
 */
class VioletGuestCartItemRepository implements VioletGuestCartItemRepositoryInterface
{
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var QuoteManagement
     */
    private $quoteManagement;
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;
   

    /**
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
        
    ) {
        $this->productRepository = $productRepository;
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * @return Magento\Catalog\Api\Data\ProductInterface
     */
    private function loadProduct($skuId)
    {
        return $this->productRepository->get($skuId);
    }

    /**
     * @param Magento\Quote\Api\Data\CartItemInterface $cartItem The item.
     * @return Magento\Quote\Api\Data\CartItemInterface Item.
     */
    public function save($cartItem)
    {
         $cartId = $cartItem->getQuoteId();
         if (!$cartId) {
             throw new InputException(
                 __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'quoteId'])
             );
         }

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $cartItem->setQuoteId($quoteIdMask->getQuoteId());

        // load the quote using the unmasked ID
        $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());

         $skuExists = false;
         foreach ($quote->getItems() as $item) {
            if ($item->getSku() == $cartItem->getSku()) {
                // set the custom price on the item and save it
                $item->setQty(intval($cartItem->getQty()));
                $item->setCustomPrice($item->getPrice());
                $item->setOriginalCustomPrice($item->getPrice());
                $item->getProduct()->setIsSuperMode(true);
                $item->save();
                $skuExists = true;
            } else {
                // re-enforce the custom price and save it
                $item->setCustomPrice($item->getPrice());
                $item->setOriginalCustomPrice($item->getPrice());
                $item->getProduct()->setIsSuperMode(true);
                $item->save(); 
            }
         }

         // if the SKU does not yet exist in the cart add it
         if (!$skuExists) {

            // load the product using the Sku
            $product=$this->productRepository->get($cartItem->getSku());

            // if a price is provided use this to override the default price
            if ($cartItem->getPrice() !== null) {
                $product->setPrice($cartItem->getPrice());
                $product->setBasePrice($cartItem->getPrice());
                $product->setCustomPrice($cartItem->getPrice());
                $product->setOriginalCustomPrice($cartItem->getPrice());
            }

            // add the product to the quote
            $quote->addProduct(
                $product,
                intval($cartItem->getQty())
            );
        }

        // save before collecting totals to prevent custom pricing from be overridden
        $quote->save();

        // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        // return the item that was part of the request
        foreach ($quote->getAllVisibleItems() as $item) {
            if (strcmp($item->getSku(), $cartItem->getSku()) === 0) {
                return $item;
            }
        }
        // fallback - return the first item in the collection
        return $quote->getAllVisibleItems()[0];
    }
}