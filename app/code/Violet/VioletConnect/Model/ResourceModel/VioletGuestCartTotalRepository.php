<?php
namespace Violet\VioletConnect\Model\ResourceModel;

use Violet\VioletConnect\Api\VioletGuestCartTotalRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductRepository;
use Violet\VioletConnect\Model\Data\VioletCalculatedCart;
use Violet\VioletConnect\Model\Data\VioletShippingMethod;

/**
 * Violet VioletGuestCartTotalRepository
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2024 Violet.io, Inc.
 * @since      1.2.0
 */
class VioletGuestCartTotalRepository implements VioletGuestCartTotalRepositoryInterface
{
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
     * @var CartTotalRepositoryInterface
     */
    private $cartTotalRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ProductRepository
     */
    private $productRepository;
   

    /**
     * @param QuoteManagement $quoteManagement
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param StoreManagerInterface $storeManager
     * @param ProductRepository $productRepository
     */
    public function __construct(
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductRepository $productRepository,
    ) {
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
    }

    /**
     * @api
     * @param string $cartId The cart ID.
     * @return \Magento\Quote\Api\Data\TotalsInterface
     */
    public function get($cartId)
    {
        if (!$cartId) {
            throw new InputException(
                __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'quoteId'])
            );
        }

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        // load the quote using the unmasked ID
        $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());

        $cartTotals = $this->cartTotalRepository->get($quoteIdMask->getQuoteId());

        // if no ext shipping info is present return the original cart totals
        if (!$this->quoteHasExtShippingInfo($quote)) {
            return $cartTotals;
        }

        // extract the external shipping info
        $decodedShippingInfo = json_decode($quote->getExtShippingInfo());

        // if a shipping price is included in the 
        if ($this->hasShippingPrice($decodedShippingInfo)) {
            // apply price as is to the shipping fields
            $cartTotals->setBaseShippingAmount($decodedShippingInfo->shipping_price);
            $cartTotals->setBaseShippingInclTax($decodedShippingInfo->shipping_price);
            $cartTotals->setShippingInclTax($decodedShippingInfo->shipping_price);
            $cartTotals->setShippingAmount($decodedShippingInfo->shipping_price);

            // recalculate the new grand total
            $grandTotal = $this->sumGrandTotal($cartTotals);
            
            // apply the grand total back to the cart totals
            $cartTotals->setGrandTotal($grandTotal);
            $cartTotals->setBaseGrandTotal($grandTotal);
        }

        return $cartTotals;
    }


    /**
     * @api
     * @param \Violet\VioletConnect\Model\Data\VioletCart
     * @return \Violet\VioletConnect\Model\Data\VioletCalculatedCart
     */
    public function calculate(\Violet\VioletConnect\Model\Data\VioletCart $violetCart)
    {
        // init the response object
        $violetCalculatedCart = new VioletCalculatedCart();

        $store = $this->storeManager->getStore();

        $quoteId = $this->quoteManagement->createEmptyCart();
        $quote = $this->quoteRepository->get($quoteId);

        $quote->setStore($store);
 
        // add items in quote
        foreach($violetCart->getItems() as $item) {
            $product=$this->productRepository->get($item->getSku());
            if ($item->getPrice() !== null && is_numeric($item->getPrice())) { 
                $product->setPrice($item->getPrice());
            }
            $quote->addProduct(
                $product,
                intval($item->getQty())
            );
        }

        // apply the billing address if one is provided
        if ($violetCart->getBillingAddress() !== null) {
            $quote->setBillingAddress($violetCart->getBillingAddress());
        }

        // apply the shipping address if one is provided
        if ($violetCart->getShippingInformation() !== null) {
            $quote->setShippingAddress($violetCart->getShippingInformation()->getShippingAddress());
        }

        // apply a discount if one is provided
        if ($violetCart->getDiscountCode() !== null) {
            $quote->setCouponCode($violetCart->getDiscountCode());
        }

        // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        // collect items for the response
        $violetCalculatedCart->setItems($quote->getAllVisibleItems());

        // collect shipping rates
        $quote->getShippingAddress()->collectShippingRates();
        $shippingRates = $quote->getShippingAddress()->getGroupedAllShippingRates();

        $output = [];
        $currency = $this->storeManager->getStore()->getBaseCurrency();
        if (isset($shippingRates)) {
            foreach ($shippingRates as $carrierRates) {
                foreach ($carrierRates as $rate) {
                    $errorMessage = $rate->getErrorMessage();
                    $shippingMethod = new VioletShippingMethod();
                    $shippingMethod->setCarrierCode($rate->getCarrier());
                    $shippingMethod->setMethodCode($rate->getMethod());
                    $shippingMethod->setCarrierTitle($rate->getCarrierTitle());
                    $shippingMethod->setMethodTitle($rate->getMethodTitle());
                    $shippingMethod->setAmount($currency->convert($rate->getPrice(), $quote->getQuoteCurrencyCode()));
                    $shippingMethod->setBaseAmount($rate->getPrice());
                    $shippingMethod->setAvailable(empty($errorMessage));
                    $shippingMethod->setErrorMessage(empty($errorMessage) ? false : $errorMessage);

                    $output[] = $shippingMethod;
                }
            }
        }
        $violetCalculatedCart->setShippingMethods($output);

        // collect quote totals
        $cartTotals = $this->cartTotalRepository->get($quoteId);
        $violetCalculatedCart->setTotals($cartTotals);

        // delete the quote
        $quote->delete($quote);

        return $violetCalculatedCart;
    }



    /**
     * @return boolean
     */
    private function quoteHasExtShippingInfo($quote) {
        $_shippingInfo = $quote->getExtShippingInfo();
        return (isset($_shippingInfo) && (strlen(trim($_shippingInfo)) > 0));
    }

    /**
     * @return boolean
     */
    private function hasShippingPrice($shippingInfo) {
        return (isset($shippingInfo) && isset($shippingInfo->shipping_price) && is_numeric($shippingInfo->shipping_price));
    }

    /**
     * @return double
     */
    private function sumGrandTotal($cartTotals) {
        return $cartTotals->getShippingAmount() + $cartTotals->getSubtotalWithDiscount() + $cartTotals->getTaxAmount();
    }
}