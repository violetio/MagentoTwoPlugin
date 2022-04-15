<?php
namespace Violet\VioletConnect\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Bootstrap;

/**
 * Violet Client
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 */
class Client extends AbstractHelper
{
    private $vToken;
    private $account;
    private $headerArray;
    private $merchantId;
    private $logger;
    protected $scopeConfig; // cannot be private, will not pass validation
    private $encryptor;
    private $messageManager;
    private $urlBuilder;
    private $violetEntityFactory;
    private $curl;

    const LOGIN_ENDPOINT = "login";
    const INIT_PRODUCT_SYNC_ENDPOINT = "sync/merchants/%s/products";
    const SYNC_PRODUCTS_ENDPOINT = "sync/merchants/%s/external/magento/2/products";
    const SYNC_SKUS_ENDPOINT = "sync/merchants/%s/external/magento/2/skus";
    const UPDATE_ORDER_ENDPOINT = "sync/merchants/%s/external/magento/2/order";
    const ORDER_SHIPPED_ENDPOINT = "sync/merchants/%s/external/magento/orders/%s/shipped";
    const ORDER_CANCELED_ENDPOINT = "sync/merchants/%s/external/magento/orders/%s/canceled";
    const ORDER_REFUNDED_ENDPOINT = "sync/merchants/%s/external/magento/orders/%s/refunded";
    const UPDATE_QTY_ENDPOINT = "sync/merchants/%s/external/magento/skus/qty";
    const SET_CREDENTIALS_ENDPOINT = "sync/merchants/%s/external/magento/credentials";

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeInterface,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Violet\VioletConnect\Model\VioletEntityFactory $violetEntityFactory
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeInterface;
        $this->encryptor = $encryptor;
        $this->messageManager = $messageManager;
        $this->urlBuilder = $urlBuilder;
        $this->violetEntityFactory = $violetEntityFactory;
    }

  /**
   * Login
   * - creates a Violet Session
   */
    public function login()
    {
        // retrieve credentials from config
        $violetUsername = $this->scopeConfig->getValue(
            'violet/general/violet_username',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $violetPassword = $this->scopeConfig->getValue(
            'violet/general/violet_password',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $violetSectionUrl = $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/violet');

        if ($violetUsername === null || $violetPassword === null) {
            // $this->messageManager->addWarning(__(
            //     "Your Violet credentials have not been added.
            //     Please <a href='".$violetSectionUrl."'>add these</a> before continuing."
            // ));
            // log this failure
            $this->logger->info("Violet credentials have not been set.");
            return;
        }

        // build request body
        $loginBody = json_encode([
        "username" => $violetUsername,
        "password" => $this->encryptor->decrypt($violetPassword)
        ]);

        // public app secret for Magento Requests only. Invalidates against other API services
        $headers = ["Content-Type: application/json", "X-Violet-App-Id: -1"];

        // make request
        $requestUrl = $this->getApiPath() . self::LOGIN_ENDPOINT;
        $loginRequest = $this->makeRequest("POST", $requestUrl, $loginBody, $headers);

        // handle response
        $httpCode = $this->getResponseHeader("http_code");
        if (strpos($httpCode, '200') !== false) {
            $this->vToken = $this->getResponseHeader("Authorization");
            $responseBody = json_decode($loginRequest);

            $this->merchantId = $responseBody->merchant_id;

            return $responseBody;
        } else {
            // $this->messageManager->addWarning(__(
            //     "Your Violet credentials are invalid.
            //     Please <a href='".$violetSectionUrl."'>update these</a> before continuing."
            // ));
            // log this failure
            $this->logger->info("Violet Username and/or Password are invalid.
            Please update your credentials then try again.");
        }
    }

  /**
   * Init Product Sync
   * - initializes magento 2 product sync
   */
    public function initProductSync()
    {
        try {
            if ($this->vToken === null) {
                $user = $this->login();
                if (!$user) {
                    throw new \Exception("Could not sign into Violet.", 1001);
                }
                $token = $user->token;
            } else {
                $token = $this->vToken;
            }

            $url = $this->getApiPath() . sprintf(self::INIT_PRODUCT_SYNC_ENDPOINT, $this->merchantId);
            $headers = self::assembleRequestHeaders();
            $headers[] = 'X-Violet-Token: ' . $token;

            $request = $this->makeRequest("POST", $url, null, $headers);

            $this->messageManager->addSuccess(__(
                "Products synced successfully!"
            ));

            return $request;
        } catch (\Exception $e) {
            $this->messageManager->addWarning(__(
                "Products could not be synced. " . $e->getMessage()
            ));
        }
    }

  /**
   * Sync Products
   * - syncs products to Violet
   * @param products
   */
    public function syncProducts($products)
    {
        try {
            if ($this->vToken === null) {
                $this->login();
            }
    
            $url = $this->getApiPath() . sprintf(self::SYNC_PRODUCTS_ENDPOINT, $this->merchantId);
            $headers = self::assembleRequestHeaders();
            $requestBody = json_encode($products);
    
            $headers[] = 'X-Violet-Hmac-Sha256: ' . $this->signRequest($requestBody);
    
            $request = $this->makeRequest("POST", $url, $requestBody, $headers);
    
            return $request;
        } catch (\Exception $e) {
            $this->logger->info("Product could not be synced. " . $e->getMessage());
        }
    }

  /**
   * Sync Skus
   * - syncs skus to Violet
   * @param skus
   */
    public function syncSkus($skus)
    {
        try {
            if ($this->vToken === null) {
                $this->login();
            }
    
            $url = $this->getApiPath() . sprintf(self::SYNC_SKUS_ENDPOINT, $this->merchantId);
            $headers = self::assembleRequestHeaders();
            $requestBody = json_encode($skus);
    
            $request = $this->makeRequest("POST", $url, $requestBody, $headers);
    
            return $request;
        } catch (\Exception $e) {
            $this->logger->info("SKU could not be synced. " . $e->getMessage());
        }
    }

  /**
   * Update Order
   * - update order record
   * @param order
   */
    public function updateOrderRecord($order)
    {
        if ($this->vToken === null) {
            $this->login();
        }

        $url = $this->getApiPath() . sprintf(self::UPDATE_ORDER_ENDPOINT, $this->merchantId);
        $headers = self::assembleRequestHeaders();


        $requestBody = json_encode($order);

        $headers[] = 'X-Violet-Hmac-Sha256: ' . $this->signRequest($requestBody);

        $request = $this->makeRequest("POST", $url, $requestBody, $headers);
        return $request;
    }

  /**
   * Order Shipped
   * - flag order as being shipping and provide tracking number
   * @param externalOrderId
   * @param trackingId
   * @param carrierName
   */
    public function orderShipped($externalOrderId, $trackingId, $carrierName)
    {
        if ($this->vToken === null) {
            $this->login();
        }

        $url = $this->getApiPath() . sprintf(self::ORDER_SHIPPED_ENDPOINT, $this->merchantId, $externalOrderId);
        $headers = self::assembleRequestHeaders();

        $requestBody = json_encode([
        "merchant_id" => $this->merchantId,
        "tracking_id" => $trackingId,
        "carrier" => $carrierName,
        'timestamp' => time()
        ]);

        $headers[] = 'X-Violet-Hmac-Sha256: ' . $this->signRequest($requestBody);

        $request = $this->makeRequest("POST", $url, $requestBody, $headers);
        return $request;
    }

  /**
   * Quantity Updated
   * - updates qty records
   * @param externalId
   * @param qty
   */
    public function qtyUpdated($externalId, $qty)
    {
        if ($this->vToken === null) {
            $this->login();
        }
        $url = $this->getApiPath() . sprintf(self::UPDATE_QTY_ENDPOINT, $this->merchantId);
        $headers = self::assembleRequestHeaders();

        $requestBody = json_encode([
        "external_id" => $externalId,
        "qty" => $qty
        ]);

        $headers[] = 'X-Violet-Hmac-Sha256: ' . $this->signRequest($requestBody);

        $request = $this->makeRequest("POST", $url, $requestBody, $headers);
        return $request;
    }

  /**
   * Order Canceled
   * - flag order as being canceled
   * @param externalOrderId
   */
    public function orderCanceled($externalOrderId)
    {
        if ($this->vToken === null) {
            $this->login();
        }

        $url = $this->getApiPath() . sprintf(self::ORDER_CANCELED_ENDPOINT, $this->merchantId, $externalOrderId);
        $headers = self::assembleRequestHeaders();

        $requestBody = json_encode([
        "merchant_id" => $this->merchantId,
        "order_id" => $externalOrderId
        ]);

        $headers[] = 'X-Violet-Hmac-Sha256: ' . $this->signRequest($requestBody);

        $request = $this->makeRequest("POST", $url, $requestBody, $headers);
        return $request;
    }

    /**
     * Order Refunded
     * - flag order as being refunded
     * @param externalOrderId
     */
      public function orderRefunded($externalOrderId)
      {
          if ($this->vToken === null) {
              $this->login();
          }

          $url = $this->getApiPath() . sprintf(self::ORDER_REFUNDED_ENDPOINT, $this->merchantId, $externalOrderId);
          $headers = self::assembleRequestHeaders();

          $requestBody = json_encode([
          "merchant_id" => $this->merchantId,
          "order_id" => $externalOrderId
          ]);

          $request = $this->makeRequest("POST", $url, $requestBody, $headers);
          return $request;
      }

  /**
   * Set Merchant Credentials
   * - persists merchant credentials to Violet
   * @param accessToken
   * @param secret
   * @param verifier
   */
    public function setMerchantCredentials($accessToken, $secret, $verifier)
    {
        try {
            if ($this->vToken === null) {
                $this->login();
            }

            $bootstrap = Bootstrap::create(BP, $_SERVER);
            $objectManager = $bootstrap->getObjectManager();
            $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');

            // create token
            $token = base64_encode(openssl_random_pseudo_bytes(30));
            $tokenEncrypt = $this->encryptor->encrypt($token);

            // load existing violet entity
            $violetEntityModel = $this->violetEntityFactory->create();
            $violetEntity = $violetEntityModel->load(1);

            $merchantId = $this->scopeConfig->getValue(
                'violet/general/violet_merchant_id',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            // update violet entity with encrypted token and merchant ID
            $violetEntity->setToken($tokenEncrypt)->setMerchantId((int)$merchantId)->save();

            // define url
            $url = $this->getApiPath() . sprintf(self::SET_CREDENTIALS_ENDPOINT, $merchantId);
            $headers = self::assembleRequestHeaders();

            $requestBody = json_encode(
                [
                "credential_part_one" => $accessToken,
                "credential_part_two" => $secret,
                "credential_part_three" => $verifier,
                "credential_part_four" => $token,
                "store_url" => $storeManager->getStore()->getBaseUrl(),
                "platform" => "magento",
                "merchant_id" => $merchantId
                ]
            );

            $response = $this->makeRequest("POST", $url, $requestBody, $headers);

            return $response;
        } catch (\Exception $e) {
            $this->logger->info("Violet merchant credentials creation failure: " . $e->getMessage());
            $this->messageManager->addWarning(__(
                "API User coult not be created or refreshed. " . $e->getMessage()
            ));
        }
    }

  /**
   * Make Request
   * - builds and sends HTTP request
   * @param method
   * @param url
   * @param body
   * @param headers
   */
    private function makeRequest($method, $url, $body = null, $headers = null)
    {
        try {
            ob_start();
            // handle headers
            if ($headers === null) {
                $headers = ['Content-Type: application/json',"X-Violet-App-Id: -1"];
            }

            // ititiate curl
            $ch = curl_init($url);

            // handle body if present
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            // handle request method
            if ($method == "POST") {
                curl_setopt($ch, CURLOPT_POST, true);
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }

            // prepare request (remove debug upon completion)
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, 1);

            // make request
            $result = curl_exec($ch);

            // parse and cache header
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($result, 0, $headerSize);
            $this->headerArray = $this->getHeadersAsArray($header);

            $responseBody = substr($result, $headerSize);

            // close connection
            curl_close($ch);
            ob_end_flush();

            return $responseBody;
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            return $e->getMessage();
        }
    }


    /**
  * Sign Request
  * @param object $requestBody
  */
  private function signRequest($requestBody)
  {
    try {
      // load existing violet entity
      $violetEntityModel = $this->violetEntityFactory->create();
      $violetEntity = $violetEntityModel->load(1);

      // update violet entity with encrypted token and merchant ID
      $tokenDecrypt = $this->encryptor->decrypt($violetEntity->getToken());
      $hmac = base64_encode(hash_hmac("sha256", $requestBody, $tokenDecrypt, true));
      return $hmac;
    } catch (\Exception $e) {
        return null;
    }
  }

  /**
   * Get Headers Array
   * Converts the header data into an array
   * @param response
   */
    private function getHeadersAsArray($response)
    {
        $headers = [];
        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));
        foreach (explode("\r\n", $header_text) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } else {
                        list ($key, $value) = explode(': ', $line);
                        $headers[$key] = $value;
            }
        }
        return $headers;
    }

  /**
   * Get Response Header
   * - returns header value at given index
   * @param key
   */
    private function getResponseHeader($key)
    {
        if (array_key_exists($key, $this->headerArray)) {
            return $this->headerArray[$key];
        }
        return null;
    }

  /**
   * Assemble Request Headers
   */
    private function assembleRequestHeaders()
    {
        return [
        "Content-Type: application/json",
        "X-Violet-App-Id: -1",
        ];
    }

  /**
   * Get Violet API Path
   */
    private function getApiPath()
    {
        $testMode = $this->scopeConfig->getValue(
            'violet/env/violet_testmode',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $pathOverride = $this->scopeConfig->getValue(
            'violet/env/violet_apipath',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($pathOverride !== null && strlen($pathOverride) >= 1) {
            return $pathOverride;
        } else if ($testMode !== '0') {
            return 'https://sandbox-api.violet.io/v1/';
        } else {
            return 'https://api.violet.io/v1/';
        }
    }
}
