<?php
namespace Violet\VioletConnect\Model\Data;

/**
 * Violet Admin User Model
 *
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
   * @var bool
   */
   private $productUpdateWebhooksEnabled;
  /**
   * @var bool
   */
   private $productDeleteWebhooksEnabled;
  /**
   * @var bool
   */
  private $orderWebhooksEnabled;
  /**
   * @var string
   */
  private $pluginVersion;

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

   /**
    * @return bool
    */
    public function getProductUpdateWebhooksEnabled()
    {
        return $this->productUpdateWebhooksEnabled;
    }

   /**
    * @return null
    */
    public function setProductUpdateWebhooksEnabled($enabled)
    {
        $this->productUpdateWebhooksEnabled = $enabled;
    }

   /**
    * @return bool
    */
    public function getProductDeleteWebhooksEnabled()
    {
        return $this->productDeleteWebhooksEnabled;
    }

   /**
    * @return null
    */
    public function setProductDeleteWebhooksEnabled($enabled)
    {
        $this->productDeleteWebhooksEnabled = $enabled;
    }

   /**
    * @return bool
    */
    public function getOrderWebhooksEnabled()
    {
        return $this->orderWebhooksEnabled;
    }

   /**
    * @return null
    */
    public function setOrderWebhooksEnabled($enabled)
    {
        $this->orderWebhooksEnabled = $enabled;
    }

    /**
    * @return string
    */
    public function getPluginVersion()
    {
        return "1.2.0";
    }
}
