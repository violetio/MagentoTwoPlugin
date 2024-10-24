<?php
namespace Violet\VioletConnect\Model\ResourceModel;

use Violet\VioletConnect\Api\VioletRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnectionFactory;
use Violet\VioletConnect\Model\Data\VioletConfiguration;
use Violet\VioletConnect\Model\Data\StoreAdmin;

/**
 * Violet VioletRepositoryInterface
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2018 Violet.io, Inc.
 * @since      1.0.1
 */
class VioletRepository implements VioletRepositoryInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var Status
     */
    private $productStatus;
    /**
     * @var Visibility
     */
    private $productVisibility;
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var VioletEntityFactory
     */
    private $violetEntityFactory;
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Violet\VioletConnect\Model\VioletEntityFactory $violetEntityFactory
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Violet\VioletConnect\Model\VioletEntityFactory $violetEntityFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->violetEntityFactory = $violetEntityFactory;
        $this->encryptor = $encryptor;
    }

    /**
     * counts visible skus
     *
     * @api
     *
     * @return int
     */
    public function skuCount()
    {
        $pCol = $this->productCollectionFactory->create();
        $pCol->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
        $pCol->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        $pCol->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()])
        ->addAttributeToFilter('visibility', ['in' => $this->productVisibility->getVisibleInSiteIds()]);

        return $pCol->getSize();
    }

    /**
     * gets visible skus
     *
     * @api
     *
     * @param int $page
     * @param int $pageSize
     *
     * @return Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function skus($page, $pageSize)
    {
        if ($page === null) {
            $page = 1;
        }
        if ($pageSize === null) {
            $page = 20;
        }
        if ($pageSize > 50) {
            $pageSize = 50;
        }

        $products = [];

        $pCol = $this->productCollectionFactory->create();
        $pCol->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
        $pCol->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        $pCol->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()])
        ->addAttributeToFilter('visibility', ['in' => $this->productVisibility->getVisibleInSiteIds()]);
        $pCol->setPageSize($pageSize)->setCurPage($page)->load();

        foreach ($pCol as $p) {
            $products[] = $this->loadProduct($p->getSku());
        }

        return $products;
    }

    /**
     * gets sku children
     *
     * @api
     *
     * @param string $sku
     *
     * @return Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function skuChildren($sku)
    {
        $products = [];

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $product = $this->productRepository->get($sku);
        $productTypeInstance = $objectManager->get('Magento\ConfigurableProduct\Model\Product\Type\Configurable');
        $childProducts = $productTypeInstance->getUsedProducts($product);

        foreach ($childProducts as $childProduct) {
            $products[] = $this->loadProduct($childProduct->getSku());
        }

        return $products;
    }

    /**
     * gets sku parent
     *
     * @api
     *
     * @param string $sku
     *
     * @return Magento\Catalog\Api\Data\ProductInterface
     */
    public function skuParent($sku)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $childProduct = $this->loadProduct($sku);

        $parentProductIds = $objectManager
        ->create('Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable')
        ->getParentIdsByChild($childProduct->getId());

        // if parents exist
        if (!empty($parentProductIds)) {
            $parentProduct = $objectManager
            ->create('Magento\Catalog\Model\Product')->load($parentProductIds[0]);
            return $this->loadProduct($parentProduct->getSku());
        } else {
            return $childProduct;
        }
    }

    /**
     * get shipments of order
     *
     * @api
     *
     * @param int $orderId
     *
     * @return Magento\Sales\Api\Data\ShipmentInterface[]
     */
    public function orderShipments($orderId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $shipmentRepositoryInterface = $objectManager->get('Magento\Sales\Api\ShipmentRepositoryInterface');
        $searchCriteriaBuilder = $objectManager->get('Magento\Framework\Api\SearchCriteriaBuilder');
        $searchCriteria = $searchCriteriaBuilder->addFilter('order_id', $orderId)->create();

        try {
            $shipments = $shipmentRepositoryInterface->getList($searchCriteria);
            return $shipments->getItems();
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
            return null;
        }
    }

    /**
     * @api
     *
     * @return \Violet\VioletConnect\Model\Data\StoreAdmin
     */
    public function storeAdmin()
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName('admin_user');

            $sqlQuery = "select * from " . $tableName . " where is_active = 1 limit 1";
            $results = $connection->fetchAll($sqlQuery);
            $adminResult = $results[0];
        
            $admin = new StoreAdmin($results);
            $admin->setEmailAddress($adminResult['email']);
            $admin->setFirstName($adminResult['firstname']);
            $admin->setLastName($adminResult['lastname']);
    
            return $admin;

        } catch (Exception $exception) {
            $logger->critical($exception->getMessage());
            return null;
        }
    }

    /**
     * @api
     * 
     * @param \Violet\VioletConnect\Model\Data\VioletConfiguration $config
     *
     * @return \Violet\VioletConnect\Model\Data\VioletConfiguration
     */
    public function violetConfiguration($configuration)
    {
        try {
            // load existing violet entity
            $violetEntityModel = $this->violetEntityFactory->create();
            $violetEntity = $violetEntityModel->load(1);

            if (!empty($configuration->getMerchantId())) {
                $violetEntity->setMerchantId($configuration->getMerchantId());
            }

            if (!empty($configuration->getToken())) {
                $violetEntity->setToken($this->encryptor->encrypt($configuration->getToken()));
            }

            if (!is_null($configuration->getProductUpdateWebhooksEnabled())) {
                $violetEntity->setProductUpdateWebhooksEnabled($configuration->getProductUpdateWebhooksEnabled());
            }

            if (!is_null($configuration->getProductDeleteWebhooksEnabled())) {
                $violetEntity->setProductDeleteWebhooksEnabled($configuration->getProductDeleteWebhooksEnabled());
            }

            if (!is_null($configuration->getOrderWebhooksEnabled())) {
                $violetEntity->setOrderWebhooksEnabled($configuration->getOrderWebhooksEnabled());
            }

            $violetEntity->save();

            $configRes = new VioletConfiguration();
            $configRes->setMerchantId($violetEntity->getMerchantId());
            $configRes->setToken($violetEntity->getToken());
            $configRes->setProductUpdateWebhooksEnabled($violetEntity->getProductUpdateWebhooksEnabled());
            $configRes->setProductDeleteWebhooksEnabled($violetEntity->getProductDeleteWebhooksEnabled());
            $configRes->setOrderWebhooksEnabled($violetEntity->getOrderWebhooksEnabled());
            return $configRes;

        } catch (Exception $exception) {
            $logger->critical($exception->getMessage());
            return null;
        }
    }

    /**
     * @api
     *
     * @return \Violet\VioletConnect\Model\Data\VioletConfiguration
     */
    public function getVioletConfiguration()
    {
        try {
            // load existing violet entity
            $violetEntityModel = $this->violetEntityFactory->create();
            $violetEntity = $violetEntityModel->load(1);

            $configRes = new VioletConfiguration();
            $configRes->setMerchantId($violetEntity->getMerchantId());
            $configRes->setToken($violetEntity->getToken());
            $configRes->setProductUpdateWebhooksEnabled($violetEntity->getProductUpdateWebhooksEnabled());
            $configRes->setProductDeleteWebhooksEnabled($violetEntity->getProductDeleteWebhooksEnabled());
            $configRes->setOrderWebhooksEnabled($violetEntity->getOrderWebhooksEnabled());
            return $configRes;

        } catch (Exception $exception) {
            $logger->critical($exception->getMessage());
            return null;
        }
    }

    /**
     * @return Magento\Catalog\Api\Data\ProductInterface
     */
    private function loadProduct($skuId)
    {
        return $this->productRepository->get($skuId);
    }
}
