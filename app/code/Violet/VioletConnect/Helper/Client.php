<?php
namespace Violet\VioletConnect\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Bootstrap;

/**
 * Violet Client
 *
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

    public const PRODUCT_EVENT_ENDPOINT = "sync/external/events/magento/product";
    public const ORDER_EVENT_ENDPOINT = "sync/external/events/magento/order";

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
     * Product Updated
     * @param externalId
     */
    public function productUpdated($externalId)
    {
        $url = $this->getApiPath() . self::PRODUCT_EVENT_ENDPOINT;
        $headers = self::assembleRequestHeaders();

        $requestBody = json_encode([
            "entity_id" => $externalId,
            "entity_type" => "PRODUCT",
            "event_type" => "PRODUCT_UPDATED"
        ]);

        $headers[] = 'X-Violet-Hmac-Sha256: ' . $this->signRequest($requestBody);

        $request = $this->makeRequest("POST", $url, $requestBody, $headers, "PRODUCT_UPDATED");
        return $request;
    }

    /**
     * Product Deleted
     * @param externalId
     */
    public function productDeleted($externalId)
    {
        $url = $this->getApiPath() . self::PRODUCT_EVENT_ENDPOINT;
        $headers = self::assembleRequestHeaders();

        $requestBody = json_encode([
            "entity_id" => $externalId,
            "entity_type" => "PRODUCT",
            "event_type" => "PRODUCT_DELETED"
        ]);

        $headers[] = 'X-Violet-Hmac-Sha256: ' . $this->signRequest($requestBody);

        $request = $this->makeRequest("POST", $url, $requestBody, $headers, "PRODUCT_DELETED");
        return $request;
    }


    /**
     * Order Shipped
     * - flag order as being shipping and provide tracking number
     * @param externalOrderId
     * @param trackingId
     * @param carrierName
     */
    public function orderShipped($externalOrderId)
    {
        $url = $this->getApiPath() . self::ORDER_EVENT_ENDPOINT;
        $headers = self::assembleRequestHeaders();

        $requestBody = json_encode([
            "entity_id" => $externalOrderId,
            "entity_type" => "ORDER",
            "event_type" => "ORDER_SHIPPED"
        ]);
        $headers[] = 'X-Violet-Hmac-Sha256: ' . $this->signRequest($requestBody);

        $request = $this->makeRequest("POST", $url, $requestBody, $headers, "ORDER_UPDATED");
        return $request;
    }


    /**
     * Order Refunded
     * - flag order as being refunded
     * @param externalOrderId
     */
    public function orderRefunded($externalOrderId)
    {
        $url = $this->getApiPath() . self::ORDER_EVENT_ENDPOINT;
        $headers = self::assembleRequestHeaders();

        $requestBody = json_encode([
            "entity_id" => $externalOrderId,
            "entity_type" => "ORDER",
            "event_type" => "ORDER_REFUNDED"
        ]);
        $headers[] = 'X-Violet-Hmac-Sha256: ' . $this->signRequest($requestBody);

        $request = $this->makeRequest("POST", $url, $requestBody, $headers, "ORDER_UPDATED");
        return $request;
    }


  /**
   * Make Request
   * - builds and sends HTTP request
   * @param method
   * @param url
   * @param body
   * @param headers
   */
    private function makeRequest($method, $url, $body = null, $headers = null, $genericEventType = null)
    {
        try {
            ob_start();
            // handle headers
            if ($headers === null) {
                $headers = ['Content-Type: application/json',"X-Violet-App-Id: -1"];
            }

            // load existing violet entity
            try {
                $violetEntityModel = $this->violetEntityFactory->create();
                $violetEntity = $violetEntityModel->load(1);
                $merchantId = $violetEntity->getMerchantId();

                if ($merchantId != null && $merchantId > 10000) {
                    $headers[] = 'X-Violet-Merchant-Id: ' . $merchantId;
                }
                
                // account for webhook enable/disable status
                if (!empty($genericEventType)) {
                    // escape the event if product update webhooks are not enabled
                    if ($genericEventType == "PRODUCT_UPDATED" && !$violetEntity->getProductUpdateWebhooksEnabled()) {
                        return null;
                    }
                    // escape the event if product delete webhooks are not enabled
                    elseif ($genericEventType == "PRODUCT_DELETED" && !$violetEntity->getProductDeleteWebhooksEnabled()) {
                        return null;
                    }
                    // escape the event if order update webhooks are not enabled
                    elseif ($genericEventType == "ORDER_UPDATED" && !$violetEntity->getOrderWebhooksEnabled()) {
                        return null;
                    }
                }

            } catch (\Exception $ignore) {}

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
        } elseif ($testMode !== '0') {
            return 'https://sandbox-api.violet.io/v1/';
        } else {
            return 'https://api.violet.io/v1/';
        }
    }
}
