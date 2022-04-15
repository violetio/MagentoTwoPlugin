<?php
namespace Violet\VioletConnect\Model\ResourceModel;

use Violet\VioletConnect\Api\VioletRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnectionFactory;
use Violet\VioletConnect\Model\Data\TokenValidation;

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
     * @api
     *
     * @param string $token
     * @param int $merchantId
     *
     * @return \Violet\VioletConnect\Model\Data\TokenValidation
     */
    public function validateAccount($token, $merchantId)
    {
      // load existing violet entity
        $violetEntityModel = $this->violetEntityFactory->create();
        $violetEntity = $violetEntityModel->load(1);
        $encryptedToken = $violetEntity->getToken();
        $tokenActual = $this->encryptor->decrypt($encryptedToken);

        $validation = new TokenValidation();
        $validation->setValidated($token === $tokenActual);
        return $validation;
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
     * @return Magento\Catalog\Api\Data\ProductInterface
     */
    private function loadProduct($skuId)
    {
        return $this->productRepository->get($skuId);
    }
}
