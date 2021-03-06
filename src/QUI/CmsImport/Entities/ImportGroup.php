<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportGroup
 *
 * Represents a QUIQQER user group that is imported
 */
class ImportGroup extends AbstractImportEntity implements CustomQuiqqerIdInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $hasAdminAccess = false;

    /**
     * @var int
     */
    protected $quiqqerId = null;

    /**
     * QUIQQER permissions for this group
     *
     * @var array
     */
    protected $quiqqerPermissions = [];

    /**
     * ImportGroup constructor.
     *
     * @param string|int $identifier - ImportGroup identifier
     * @param string $name - Project name
     * @param bool $hasAdminAccess (optional) - Group users have access to QUIQQER backend
     * @param array $attributes (optional) - Additional attributes
     */
    public function __construct($identifier, $name, $hasAdminAccess, $attributes = [])
    {
        $this->name           = $name;
        $this->hasAdminAccess = $hasAdminAccess;

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function hasAdminAccess()
    {
        return $this->hasAdminAccess;
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
     * Get the import section the ImportEntitiy belongs to
     *
     * @return string
     */
    public function getImportSection()
    {
        return QUI\CmsImport\Import::IMPORT_SECTION_GROUPS;
    }

    /**
     * Get QUIQQER permissions ($permissions => $permissionValue) for this group
     *
     * @return array
     */
    public function getQuiqqerPermissions(): array
    {
        return $this->quiqqerPermissions;
    }

    /**
     * Set QUIQQER permissions ($permissions => $permissionValue) for this group
     *
     * @param array $quiqqerPermissions
     */
    public function setQuiqqerPermissions(array $quiqqerPermissions): void
    {
        $this->quiqqerPermissions = $quiqqerPermissions;
    }
}
