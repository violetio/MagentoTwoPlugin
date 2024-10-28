<?php
namespace Violet\VioletConnect\Model\ResourceModel;

use Violet\VioletConnect\Api\VioletOrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductRepository;
use Violet\VioletConnect\Model\Data\VioletCalculatedCart;
use Violet\VioletConnect\Model\Data\VioletShippingMethod;

/**
 * Violet VioletOrderRepository
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2024 Violet.io, Inc.
 * @since      1.2.0
 */
class VioletOrderRepository implements VioletOrderRepositoryInterface
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
     * @param \Violet\VioletConnect\Model\Data\VioletCart
     * @return int
     */
    public function createOrder(\Violet\VioletConnect\Model\Data\VioletCart $violetCart)
    {
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
        $quote->setBillingAddress($violetCart->getBillingAddress());

        // apply the shipping address if one is provided
        $quote->setShippingAddress($violetCart->getShippingInformation()->getShippingAddress());

        $quote->setCustomerFirstname($violetCart->getShippingInformation()->getShippingAddress()->getFirstName());
		$quote->setCustomerLastname($violetCart->getShippingInformation()->getShippingAddress()->getLastName());
		$quote->setCustomerEmail($violetCart->getShippingInformation()->getShippingAddress()->getEmail());
		$quote->setCustomerIsGuest(true);

        // apply a discount if one is provided
        if ($violetCart->getDiscountCode() !== null) {
            $quote->setCouponCode($violetCart->getDiscountCode());
        }

        // compose and apply custom shipping info if any exists
        $quote->setExtShippingInfo($this->composeExtShippingInfo($violetCart->getShippingInformation(), $violetCart));

        // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod($violetCart->getShippingInformation()->getShippingCarrierCode() . '_' . $violetCart->getShippingInformation()->getShippingMethodCode()); //shipping method
        $quote->setPaymentMethod('violet'); //payment method
        $quote->setInventoryProcessed(true); // reduce inventory
        $quote->save(); //Now Save quote and your quote is ready
 
        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => 'violet']);
 
        // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        return $this->quoteManagement->placeOrder($quoteId, null);
    }

     /**
     * @api
     * @param \Violet\VioletConnect\Model\Data\VioletCart
     * @return string maskedQuoteId
     */
    public function createInitializedCart(\Violet\VioletConnect\Model\Data\VioletCart $violetCart)
    {
        $store = $this->storeManager->getStore();

        $quoteId = $this->quoteManagement->createEmptyCart();
        $quote = $this->quoteRepository->get($quoteId);

        $quote->setStore($store);

        if ($violetCart !== null) {

            // add items in quote
            if ($violetCart->getItems() !== null) {
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
                $quote->setInventoryProcessed(true);
            }

            // apply the billing address if one is provided
            if ($violetCart->getBillingAddress() !== null) {
                $quote->setBillingAddress($violetCart->getBillingAddress());
            }

            // apply the shipping information if any is provided
            if ($violetCart->getShippingInformation() !== null && $violetCart->getShippingInformation()->getShippingAddress() !== null) {
                $quote->setShippingAddress($violetCart->getShippingInformation()->getShippingAddress());
                // apply customer level data
                $quote->setCustomerFirstname($violetCart->getShippingInformation()->getShippingAddress()->getFirstName());
                $quote->setCustomerLastname($violetCart->getShippingInformation()->getShippingAddress()->getLastName());
                $quote->setCustomerEmail($violetCart->getShippingInformation()->getShippingAddress()->getEmail());
                $quote->setCustomerIsGuest(true);

                    // compose and apply custom shipping info if any exists
                $quote->setExtShippingInfo($this->composeExtShippingInfo($violetCart->getShippingInformation(), $violetCart));

                if ($violetCart->getShippingInformation()->getShippingCarrierCode() !== null && $violetCart->getShippingInformation()->getShippingMethodCode() !== null) {
                    $shippingAddress = $quote->getShippingAddress();
                    $shippingAddress
                    ->setCollectShippingRates(true)
                    ->collectShippingRates()
                    ->setShippingMethod($violetCart->getShippingInformation()->getShippingCarrierCode() . '_' . $violetCart->getShippingInformation()->getShippingMethodCode());
                }
            }

            // apply a discount if one is provided
            if ($violetCart->getDiscountCode() !== null) {
                $quote->setCouponCode($violetCart->getDiscountCode());
            }

            // Collect Totals & Save Quote
            $quote->collectTotals()->save();
        }

        $quote->save();

        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $quoteIdMask->setQuoteId($quoteId)->save();
        return $quoteIdMask->getMaskedId();
    }

    /**
     * @param VioletShippingInformation $addressInformation
     */
    private function composeExtShippingInfo(
        \Violet\VioletConnect\Model\Data\VioletShippingInformation $addressInformation,
        \Violet\VioletConnect\Model\Data\VioletCart $violetCart
    ) {
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
        if ($violetCart->getTaxAmount() !== null && is_numeric($violetCart->getTaxAmount())) {
            $_customShippingInfo["tax_amount"] = $violetCart->getTaxAmount();
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