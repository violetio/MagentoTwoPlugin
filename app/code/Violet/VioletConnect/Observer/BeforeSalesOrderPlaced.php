<?php
namespace Violet\VioletConnect\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;

/**
 * Violet Before Order Placed
 *
 * @copyright  2024 Violet.io, Inc.
 * @since      1.2.0
 */
class BeforeSalesOrderPlaced implements ObserverInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    public function __construct(
      \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
            $this->quoteRepository = $quoteRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            // obtain the order interceptor from the observer
            $order = $observer->getOrder();

            // if this is a violet sourced order perform additional checks
            if ($this->isVioletSourcedOrder($order)) {

                // load the quote using the ID
                $quote = $this->quoteRepository->get($order->getQuoteId());
    
                // if external shipping info was applied to the quote
                if ($this->quoteHasExtShippingInfo($quote)) {
                    $shippingInfo = json_decode($quote->getExtShippingInfo());

                    $_hasShippingPrice = $this->hasShippingPrice($shippingInfo);
                    $_hasTaxAmount = $this->hasTaxAmount($shippingInfo);
        
                    // override the shipping price if a custom price has been provided
                    if ($_hasShippingPrice || $_hasTaxAmount) {

                        // apply custom price to shipping price fields
                        if ($_hasShippingPrice) {
                            $order->setBaseShippingAmount($shippingInfo->shipping_price);
                            $order->setBaseShippingInclTax($shippingInfo->shipping_price);
                            $order->setShippingInclTax($shippingInfo->shipping_price);
                            $order->setShippingAmount($shippingInfo->shipping_price);
                        }
            
                        // apply custom tax amount if present
                        if ($_hasTaxAmount) {
                            $order->setTaxAmount($shippingInfo->tax_amount);
                            $order->setBaseTaxAmount($shippingInfo->tax_amount);
                        }

                        // calculate the new grand total
                        $grandTotal = $this->sumGrandTotal($order);
            
                        // apply grand total
                        $order->setGrandTotal($grandTotal);
                        $order->setBaseGrandTotal($grandTotal);
                        $order->setBaseTotalDue($grandTotal);
                        $order->setTotalDue($grandTotal);
                    }

                    // override the shipping description if a custom description has been provided
                    if ($this->hasShippingDescription($shippingInfo)) {
                        $order->setShippingDescription($shippingInfo->shipping_description);
                    }
                }
            }
        } catch (\Exception $e) {}
    }

    /**
     * @return boolean
     */
    private function isVioletSourcedOrder($order) {
        if (is_null($order)) return false;
        if (is_null($order->getPayment())) return false;
        if (is_null($order->getPayment()->getMethod())) return false;
        return (strcmp($order->getPayment()->getMethod(), "violet") === 0);
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
     * @return boolean
     */
    private function hasShippingDescription($shippingInfo) {
        return (isset($shippingInfo) && isset($shippingInfo->shipping_description));
    }

    /**
     * @return boolean
     */
    private function hasTaxAmount($shippingInfo) {
        return (isset($shippingInfo) && isset($shippingInfo->tax_amount) && is_numeric($shippingInfo->tax_amount));
    }

    /**
     * @return double
     */
    private function sumGrandTotal($orderTotals) {
        return $orderTotals->getShippingAmount() + $orderTotals->getSubtotal() + $orderTotals->getTaxAmount() + $orderTotals->getDiscountAmount();
    }
}
