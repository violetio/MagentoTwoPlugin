<?php
namespace Violet\VioletConnect\Model\Data;

/**
 * Violet Calculated Cart
 *
 * @copyright  2024 Violet.io, Inc.
 * @since      1.2.0
 */
class VioletCalculatedCart
{
  /**
   * @var Magento\Quote\Api\Data\CartItemInterface[]
   */
    private $items;

   /**
    * @var Magento\Quote\Api\Data\ShippingMethodInterface[]
    */
    private $shippingMethods;

   /**
    * @var Magento\Quote\Api\Data\TotalsInterface
    */
    private $totals;
    
   /**
    * @return Magento\Quote\Api\Data\CartItemInterface[]
    */
    public function getItems() {
        return $this->items;
    }

   /**
    * @param Magento\Quote\Api\Data\CartItemInterface[]
    * @return null
    */
    public function setItems($items) {
        $this->items = $items;
    }

   /**
    * @return Magento\Quote\Api\Data\ShippingMethodInterface[]
    */
    public function getShippingMethods() {
        return $this->shippingMethods;
    }

   /**
    * @param Magento\Quote\Api\Data\ShippingMethodInterface[]
    * @return null
    */
    public function setShippingMethods($shippingMethods) {
        $this->shippingMethods = $shippingMethods;
    }

   /**
    * @return Magento\Quote\Api\Data\TotalsInterface
    */
    public function getTotals() {
        return $this->totals;
    }

   /**
    * @param Magento\Quote\Api\Data\TotalsInterface
    * @return null
    */
    public function setTotals($totals) {
        $this->totals = $totals;
    }    
}