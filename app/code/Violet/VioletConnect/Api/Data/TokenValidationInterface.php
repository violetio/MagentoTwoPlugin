<?php
namespace Violet\VioletConnect\Api\Data;

/**
 * Violet Token Validation Interface
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 */
interface TokenValidationInterface extends \Magento\Framework\Api\CustomAttributesDataInterface
{
   /**
    * @return boolean|null
    */
    public function getValidated();

   /**
    * @param boolean|null $validated
    * @return null
    */
    public function setValidated($validated);
}
