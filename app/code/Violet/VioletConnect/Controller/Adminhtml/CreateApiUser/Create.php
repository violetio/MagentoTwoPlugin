<?php

namespace Violet\VioletConnect\Controller\Adminhtml\CreateApiUser;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\CsrfAwareActionInterface;

/**
 * Violet API User Creation
 *
 * create and authorizes Violet REST API User then
 * notifies Violet of credentials
 *
 * @copyright  2018 Violet.io, Inc.
 * @since      1.0.1
 */
class Create extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private $integrationName = 'Violet';
    private $integrationEmail = 'integrations@violet.io';
    private $vClient;
    private $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Violet\VioletConnect\Helper\Client $vClient,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeInterface
    ) {

        parent::__construct($context);
        $this->vClient = $vClient;
        $this->scopeConfig = $scopeInterface;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {
        $this->createIntegration();
    }

  /**
   * Create new Integration
   */
    private function createIntegration()
    {
        $bootstrap = Bootstrap::create(BP, $_SERVER);
        $objectManager = $bootstrap->getObjectManager();
        $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');

        // check for existing violet integration
        $existingIntegration = $objectManager->get('Magento\Integration\Model\IntegrationFactory')
        ->create()->load($this->integrationName, 'name')->getData();

        if (empty($existingIntegration)) {
            $integrationData =  [
            'name' => $this->integrationName,
            'email' => $this->integrationEmail,
            'status' => '1',
            'endpoint' => $this->getVioletApiPath(),
            'setup_type' => '0'
            ];

            try {
                  // Create Integration
                  $integrationFactory = $objectManager->get('Magento\Integration\Model\IntegrationFactory')->create();
                  $integration = $integrationFactory->setData($integrationData);
                  $integration->save();
                  $integrationId = $integration->getId();
                  $consumerName = 'Integration' . $integrationId;

                  // Create Consumer
                  $oauthService = $objectManager->get('Magento\Integration\Model\OauthService');
                  $consumer = $oauthService->createConsumer(['name' => $consumerName]);
                  $consumerId = $consumer->getId();
                  $integration->setConsumerId($consumer->getId());
                  $integration->save();

                  // Grant Permissions
                  $authrizeService = $objectManager->get('Magento\Integration\Model\AuthorizationService');
                  $authrizeService->grantAllPermissions($integrationId);

                  // Activate and Authorize
                  $token = $objectManager->get('Magento\Integration\Model\Oauth\Token');
                  $uri = $token->createVerifierToken($consumerId);
                  $token->setType('access');
                  $token->save();
     
            } catch (\Exception $e) {
                // pass
            }
        } else {
            $token = $objectManager->get('Magento\Integration\Model\Oauth\Token');
            $token->loadByConsumerIdAndUserType($existingIntegration['consumer_id'], 1);
            if ($token != null && !empty($token->getData())) {
                $oauthHelper = $objectManager->get('Magento\Framework\Oauth\Helper\Oauth');

                $token->setToken($oauthHelper->generateToken());
                $token->setSecret($oauthHelper->generateTokenSecret());
                $token->setVerifier($oauthHelper->generateVerifier());
                $token->save();
            } else {
                $token = $objectManager->get('Magento\Integration\Model\Oauth\Token');
                $token->createVerifierToken($existingIntegration['consumer_id']);
                $token->setType('access');
                $token->save();
            }
        }
    }

  /**
   * Get Violet API Path
   */
    private function getVioletApiPath()
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