<?php
namespace Violet\VioletConnect\Model\Data;

/**
 * Violet Shipping Method
 *
 * @copyright  2024 Violet.io, Inc.
 * @since      1.2.0
 */
class VioletShippingMethod
{
   /**
    * @var string
    */
    private $carrierCode;
   /**
    * @var string
    */
    private $methodCode;
   /**
    * @var string
    */
    private $carrierTitle;
   /**
    * @var string
    */
    private $methodTitle;
   /**
    * @var double
    */
    private $amount;
   /**
    * @var double
    */
    private $baseAmount;
   /**
    * @var boolean
    */
    private $available;
   /**
    * @var string
    */
    private $errorMessage;
   /**
    * @var double
    */
    private $priceExclTax;
   /**
    * @var double
    */
    private $priceInclTax;


    /**
     * @return string
     */
    public function getCarrierCode() {
        return $this->carrierCode;
    }
    /**
     * @param string
     * @return null
     */
    public function setCarrierCode($carrierCode) {
        return $this->carrierCode = $carrierCode;
    }

    /**
     * @return string
     */
    public function getMethodCode() {
        return $this->methodCode;
    }
    /**
     * @param string
     * @return null
     */
    public function setMethodCode($methodCode) {
        return $this->methodCode = $methodCode;
    }

    /**
     * @return string
     */
    public function getCarrierTitle() {
        return $this->carrierTitle;
    }
   /**
     * @param string
     * @return null
     */
    public function setCarrierTitle($carrierTitle) {
        return $this->carrierTitle = $carrierTitle;
    }

    /**
     * @return string
     */
    public function getMethodTitle() {
        return $this->methodTitle;
    }
    /**
     * @param string
     * @return null
     */
    public function setMethodTitle($methodTitle) {
        return $this->methodTitle = $methodTitle;
    }

    /**
     * @return double
     */
    public function getAmount() {
        return $this->amount;
    }
    /**
     * @param double
     * @return null
     */
    public function setAmount($amount) {
        return $this->amount = $amount;
    }

    /**
     * @return double
     */
    public function getBaseAmount() {
        return $this->baseAmount;
    }
    /**
     * @param double
     * @return null
     */
    public function setBaseAmount($baseAmount) {
        return $this->baseAmount = $baseAmount;
    }

    /**
     * @return boolean
     */
    public function getAvailable() {
        return $this->available;
    }
    /**
     * @param boolean
     * @return null
     */
    public function setAvailable($available) {
        return $this->available = $available;
    }

     /**
     * @return \Magento\Quote\Api\Data\ShippingMethodExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return null;
    }

    /**
     *
     * @param \Magento\Quote\Api\Data\ShippingMethodExtensionInterface $extensionAttributes
     * @return null
     */
    public function setExtensionAttributes(
        \Magento\Quote\Api\Data\ShippingMethodExtensionInterface $extensionAttributes
    ) {}

    /**
     * @return string
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }
    /**
     * @param string
     * @return null
     */
    public function setErrorMessage($errorMessage) {
        return $this->errorMessage = $errorMessage;
    }

    /**
     * @return double
     */
    public function getPriceExclTax() {
        return $this->priceExclTax;
    }
    /**
     * @param double
     * @return null
     */
    public function setPriceExclTax($priceExclTax) {
        return $this->priceExclTax = $priceExclTax;
    }

    /**
     * @return double
     */
    public function getPriceInclTax() {
        return $this->priceInclTax;
    }
    /**
     * @param double
     * @return null
     */
    public function setPriceInclTax($priceInclTax) {
        return $this->priceInclTax = $priceInclTax;
    }
}
