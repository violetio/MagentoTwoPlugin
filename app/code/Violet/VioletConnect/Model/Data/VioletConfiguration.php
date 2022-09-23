<?php
namespace Violet\VioletConnect\Model\Data;

/**
 * Violet Admin User Model
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2022 Violet.io, Inc.
 * @since      1.1.0
 */
class VioletConfiguration
{
  /**
   * @var int
   */
    private $merchantId;
  /**
   * @var string
   */
  private $token;

  /**
   * @return int
   */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

  /**
   * @return null
   */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;
    }

  /**
   * @return string
   */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return null
     */
    public function setToken($token)
    {
        $this->token = $token;
    }
}
