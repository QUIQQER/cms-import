<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportUser
 *
 * Represents a QUIQQER user that is imported
 */
class ImportUser extends AbstractImportEntity
{
    /**
     * @var string
     */
    protected $username;

    /**
     * @var int
     */
    protected $quiqqerUserId = null;

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
     * @param int $quiqqerUserId
     */
    public function setQuiqqerUserId($quiqqerUserId)
    {
        $this->quiqqerUserId = $quiqqerUserId;
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
     * @return int
     */
    public function getQuiqqerUserId()
    {
        return $this->quiqqerUserId;
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
