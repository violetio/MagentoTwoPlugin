<?php
namespace Violet\VioletConnect\Model\Data;

/**
 * Violet Product Wrapper Model
 *
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 */
class ProductWrapper
{
  /**
   * @var Magento\Catalog\Model\Product
   */
    private $product;
  /**
   * @var Magento\Catalog\Model\Product[]
   */
    private $children;
  /**
   * @var Magento\Catalog\Model\Option[]
   */
    private $options;
  /**
   * @var Magento\Catalog\Model\Link[]
   */
    private $links;

  /**
   * @return Magento\Catalog\Api\Data\ProductInterface
   */
    public function getProduct()
    {
        return $this->product;
    }

  /**
   * @return null
   */
    public function setProduct($product)
    {
        $this->product = $product;
    }

  /**
   * @return Magento\Catalog\Api\Data\ProductInterface[]
   */
    public function getChildren()
    {
        return $this->children;
    }

  /**
   * @param Magento\Catalog\Api\Data\ProductInterface[]
   * @return null
   */
    public function setChildren($children)
    {
        $this->children = $children;
    }

  /**
   * @return Magento\Catalog\Api\Data\ProductOptionInterface[]
   */
    public function getOptions()
    {
        return $this->options;
    }

  /**
   * @param Magento\Catalog\Api\Data\ProductOptionInterface[]
   * @return null
   */
    public function setOptions($options)
    {
        $this->options = $options;
    }

  /**
   * @return Magento\Catalog\Api\Data\ProductLinkInterface[]
   */
    public function getLinks()
    {
        return $this->links;
    }

  /**
   * @param Magento\Catalog\Api\Data\ProductLinkInterface[]
   * @return null
   */
    public function setLinks($links)
    {
        $this->links = $links;
    }
}
