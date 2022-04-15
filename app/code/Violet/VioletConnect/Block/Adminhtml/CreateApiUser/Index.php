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
        $bootstrap = Bootstrap::create(BP, $_SERVER);
        $objectManager = $bootstrap->getObjectManager();
        $integrationExists = $objectManager->get('Magento\Integration\Model\IntegrationFactory')
        ->create()->load($this->integrationName, 'name')->getData();

        $this->logger->info(!empty($integrationExists));
        return (!empty($integrationExists));
    }

    public function getAction()
    {
        return $this->getUrl('violetconnect/createapiuser/create/index');
    }
}