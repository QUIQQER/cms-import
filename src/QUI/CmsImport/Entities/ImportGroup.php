<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportGroup
 *
 * Represents a QUIQQER user group that is imported
 */
class ImportGroup extends AbstractImportEntity
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
    protected $quiqqerGroupId = null;

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
     * @param int $quiqqerGroupId
     */
    public function setQuiqqerGroupId($quiqqerGroupId)
    {
        $this->quiqqerGroupId = $quiqqerGroupId;
    }

    /**
     * @return int
     */
    public function getQuiqqerGroupId()
    {
        return $this->quiqqerGroupId;
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
}
