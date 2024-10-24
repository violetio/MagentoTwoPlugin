<?php

namespace Violet\VioletConnect\Block\Adminhtml\CreateApiUser;

use Magento\Framework\App\Bootstrap;

class Index extends \Magento\Backend\Block\Widget\Container
{

    private $integrationName = 'Violet';
    private $integrationEmail = 'support@violet.io';
    private $logger;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {

        parent::__construct($context, $data);
        $this->logger = $context->getLogger();
    }

    public function integrationExists()
    {
        return (!empty($this->getExistingIntegration()));
    }

    public function getToken() 
    {
        $bootstrap = Bootstrap::create(BP, $_SERVER);
        $objectManager = $bootstrap->getObjectManager();
        $existingIntegration = $this->getExistingIntegration();
        $token = $objectManager->get('Magento\Integration\Model\Oauth\Token');
        $token->loadByConsumerIdAndUserType($existingIntegration['consumer_id'], 1);
        return $token;
    }

    private function getExistingIntegration()
    {
        $bootstrap = Bootstrap::create(BP, $_SERVER);
        $objectManager = $bootstrap->getObjectManager();
        $existingIntegration = $objectManager->get('Magento\Integration\Model\IntegrationFactory')
        ->create()->load($this->integrationName, 'name')->getData();
        return $existingIntegration;
    }

    public function getAction()
    {
        return $this->getUrl('violetconnect/createapiuser/create/index');
    }
}
