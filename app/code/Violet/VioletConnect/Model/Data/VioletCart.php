<?php
namespace Violet\VioletConnect\Model\Data;

/**
 * Violet Cart
 *
 * @copyright  2024 Violet.io, Inc.
 * @since      1.2.0
 */
class VioletCart
{
  /**
   * @var Magento\Quote\Api\Data\CartItemInterface[]
   */
    private $items;
  /**
   * @var Violet\VioletConnect\Model\Data\VioletShippingInformation
   */
    private $shippingInformation;
  /**
   * @var Magento\Quote\Api\Data\AddressInterface
   */
    private $billingAddress;
  /**
   * @var string
   */
    private $discountCode;
  /**
   * @var double
   */
  private $taxAmount;


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
   * @return Violet\VioletConnect\Model\Data\VioletShippingInformation
   */
  public function getShippingInformation() {
    return $this->shippingInformation;
  }

  /**
   * @param Violet\VioletConnect\Model\Data\VioletShippingInformation
   * @return null
   */
  public function setShippingInformation($shippingInformation) {
    $this->shippingInformation = $shippingInformation;
  }

  /**
   * @return Magento\Quote\Api\Data\AddressInterface
   */
  public function getBillingAddress() {
    return $this->billingAddress;
  }

  /**
   * @param Magento\Quote\Api\Data\AddressInterface
   * @return null
   */
  public function setBillingAddress($billingAddress) {
    $this->billingAddress = $billingAddress;
  }

  /**
   * @return string
   */
  public function getDiscountCode() {
    return $this->discountCode;
  }

  /**
   * @param string
   * @return null
   */
  public function setDiscountCode($discountCode) {
    $this->discountCode = $discountCode;
  }

  /**
   * @return double
   */
  public function getTaxAmount() {
    return $this->taxAmount;
  }

  /**
   * @param double
   * @return null
   */
  public function setTaxAmount($taxAmount) {
    $this->taxAmount = $taxAmount;
  }
}