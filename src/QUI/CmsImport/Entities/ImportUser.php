<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportUser
 *
 * Represents a QUIQQER user that is imported
 */
class ImportUser extends AbstractImportEntity implements CustomQuiqqerIdInterface
{
    /**
     * @var string
     */
    protected $username;

    /**
     * @var int
     */
    protected $quiqqerId = null;

    /**
     * @var int|string[] - Group identifiers
     */
    protected $groups = [];

    /**
     * @var string
     */
    protected $passwordHash = null;

    /**
     * SuperUser flag
     *
     * @var bool
     */
    protected $isSU = false;

    /**
     * Administrator flag - administrators have QUIQQER backend access
     *
     * @var bool
     */
    protected $isAdmin = false;

    /**
     * Collection of user addresses
     *
     * @var array
     */
    protected $addresses = [];

    /**
     * ImportUser constructor.
     *
     * @param string|int $identifier - ImportGroup identifier
     * @param string $username - Username
     * @param array $attributes (optional) - Additional attributes
     */
    public function __construct($identifier, $username, $attributes = [])
    {
        $this->username = $username;

        $this->setAttributes($attributes);
        parent::__construct($identifier);
    }

    /**
     * @return int|string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set QUIQQER ID
     *
     * @param int $id
     * @return void
     */
    public function setQuiqqerId(int $id)
    {
        $this->quiqqerId = $id;
    }

    /**
     * Return QUIQQER ID
     *
     * @return int|null
     */
    public function getQuiqqerId()
    {
        return $this->quiqqerId;
    }

    /**
     * Set groups to the user
     *
     * @param array $groups - An array of unique ImportGroup identifiers
     * @return void
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    /**
     * @return int|string[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return string
     */
    public function getPasswordHash()
    {
        return $this->passwordHash;
    }

    /**
     * @param string $passwordHash
     */
    public function setPasswordHash($passwordHash)
    {
        $this->passwordHash = $passwordHash;
    }

    /**
     * @return bool
     */
    public function isSU()
    {
        return $this->isSU;
    }

    /**
     * @param bool $isSU
     */
    public function setIsSU($isSU)
    {
        $this->isSU = $isSU;
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    /**
     * @param bool $isAdmin
     */
    public function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    /**
     * @param array $address
     *
     * Available $address keys:
     * [
     *      'default' => true / false
     *      'salutation',
     *      'firstname',
     *      'lastname',
     *      'mail',
     *      'company',
     *      'street_no',
     *      'zip',
     *      'city',
     *      'country',
     *      'phone' => [123, 312],
     *      'mobile' => [123, 312],
     *      'fax' => [123, 321]
     * ]
     */
    public function addAddress($address)
    {
        $this->addresses[] = $address;
    }

    /**
     * @return array
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * Get the import section the ImportEntitiy belongs to
     *
     * @return string
     */
    public function getImportSection()
    {
        return QUI\CmsImport\Import::IMPORT_SECTION_USERS;
    }
}
