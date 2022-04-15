<?php
namespace Violet\VioletConnect\Model\Data;

/**
 * Violet Token Validation Model
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 */
class TokenValidation
{
    private $validated;

   /**
    * @return boolean
    */
    public function getValidated()
    {
        return $this->validated;
    }

   /**
    * @param boolean|null $validated
    * @return null
    */
    public function setValidated($validated)
    {
        $this->validated = $validated;
    }
}
