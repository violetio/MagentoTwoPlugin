<?php
namespace Violet\VioletConnect\Model\Data;

/**
 * Violet Admin User Model
 *
 * @copyright  2022 Violet.io, Inc.
 * @since      1.1.0
 */
class StoreAdmin
{
  /**
   * @var string
   */
    private $emailAddress;
  /**
   * @var string
   */
    private $firstName;
  /**
   * @var string
   */
    private $lastName;


  /**
   * @return string
   */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

  /**
   * @return null
   */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;
    }

  /**
   * @return string
   */
    public function getFirstName()
    {
        return $this->firstName;
    }

  /**
   * @return null
   */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

  /**
   * @return string
   */
    public function getLastName()
    {
        return $this->lastName;
    }

  /**
   * @return null
   */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }
}
