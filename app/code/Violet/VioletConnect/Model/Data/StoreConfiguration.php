<?php
namespace Violet\VioletConnect\Model\Data;

/**
 * Violet Store Configuration Model
 *
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 */
class StoreConfiguration
{
  /**
   * @var string
   */
    private $weightUnit;
  /**
   * @var string
   */
    private $currencyCode;
  /**
   * @var string
   */
    private $baseUrl;
  /**
   * @var string
   */
    private $name;

  /**
   * @return string
   */
    public function getWeightUnit()
    {
        return $this->weightUnit;
    }

  /**
   * @return null
   */
    public function setWeightUnit($weightUnit)
    {
        $this->weightUnit = $weightUnit;
    }

  /**
   * @return string
   */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

  /**
   * @return null
   */
    public function setCurrencyCode($currencyCode)
    {
        $this->currencyCode = $currencyCode;
    }

  /**
   * @return string
   */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

  /**
   * @return null
   */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

  /**
   * @return string
   */
    public function getName()
    {
        return $this->name;
    }

  /**
   * @return null
   */
    public function setName($name)
    {
        $this->name = $name;
    }
}
