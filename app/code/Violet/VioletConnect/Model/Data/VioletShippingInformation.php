<?php
namespace Violet\VioletConnect\Model\Data;

/**
 * Violet Shipping Information
 *
 * @copyright  2024 Violet.io, Inc.
 * @since      1.2.0
 */
class VioletShippingInformation extends \Magento\Checkout\Model\ShippingInformation
{
 
  /**
   * @var string
   */
    private $shippingMethodDescription;
  /**
   * @var double
   */
   private $shippingMethodPrice;


  /**
   * @return string
   */
    public function getShippingMethodDescription()
    {
        return $this->shippingMethodDescription;
    }

  /**
   * @param string
   * @return null
   */
    public function setShippingMethodDescription($shippingMethodDescription)
    {
        $this->shippingMethodDescription = $shippingMethodDescription;
    }

  /**
   * @return double
   */
  public function getShippingMethodPrice()
  {
      return $this->shippingMethodPrice;
  }

/**
 * @return null
 */
  public function setShippingMethodPrice($shippingMethodPrice)
  {
      $this->shippingMethodPrice = $shippingMethodPrice;
  }
}
