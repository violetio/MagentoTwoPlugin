<?php

namespace Violet\VioletConnect\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Violet Product Sync
 *
 * create and authorizes Violet REST API User then
 * notifies Violet of credentials
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2018 Violet.io, Inc.
 * @since      1.0.1
 */
class InitProductSync extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private $vClient;
    protected $scopeConfig;
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
        $this->initProductSync();
    }

  /**
   * Initialize Product Sync
   */
    private function initProductSync()
    {
        $bootstrap = Bootstrap::create(BP, $_SERVER);
        $objectManager = $bootstrap->getObjectManager();
        $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $this->vClient->initProductSync();
    }
}