<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportPermission
 *
 * Represents a QUIQQER permission
 */
class ImportPermission extends AbstractImportEntity
{
    /**
     * Possible Permission types ("What values can the permission hold?")
     */
    const TYPE_BOOL             = 'bool';
    const TYPE_STRING           = 'string';
    const TYPE_INT              = 'int';
    const TYPE_ARRAY            = 'array';
    const TYPE_GROUP            = 'group';
    const TYPE_GROUPS           = 'groups';
    const TYPE_USER             = 'user';
    const TYPE_USERS            = 'users';
    const TYPE_USERS_AND_GROUPS = 'users_and_groups';

    /**
     * Possible permission areas ("Who can hold the permission?")
     */
    const AREA_GLOBAL  = 'global';
    const AREA_USER    = 'user';
    const AREA_GROUPS  = 'groups';
    const AREA_SITE    = 'site';
    const AREA_PROJECT = 'project';
    const AREA_MEDIA   = 'media';

    /**
     * @var string
     */
    protected $permission;

    /**
     * @var string
     */
    protected $area;

    /**
     * @var string
     */
    protected $type;

    /**
     * Permission translations (title)
     *
     * @var array
     */
    protected $translationsTitle = [];

    /**
     * Permission translations (description)
     *
     * @var array
     */
    protected $translationsDescription = [];

    /**
     * ImportPermission constructor.
     *
     * @param string $identifier - Unique identifier
     * @param string $permission - Permission name
     * @param string $type (optional) - Permission type (must be one of self::TYPE_*)
     * @param string $area (optional) - Permission area (must be one of self::AREA_*)
     * @return void
     */
    public function __construct($identifier, $permission, $type = self::TYPE_BOOL, $area = self::AREA_GLOBAL)
    {
        $this->permission = $permission;
        $this->type       = $type;
        $this->area       = $area;
        parent::__construct($identifier);
    }

    /**
     * Set translation text for a permission title
     *
     * @param string $lang
     * @param string $translation
     * @return void
     */
    public function setTitleTranslation($lang, $translation)
    {
        $this->translationsTitle[$lang] = $translation;
    }

    /**
     * Set translation text for a permission description
     *
     * @param string $lang
     * @param string $translation
     * @return void
     */
    public function setDescriptionTranslation($lang, $translation)
    {
        $this->translationsDescription[$lang] = $translation;
    }

    /**
     * @return array
     */
    public function getTranslationsTitle()
    {
        return $this->translationsTitle;
    }

    /**
     * @return array
     */
    public function getTranslationsDescription()
    {
        return $this->translationsDescription;
    }

    /**
     * @return string
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * @return string
     */
    public function getPermissionArea()
    {
        return $this->area;
    }

    /**
     * @return string
     */
    public function getPermissionType()
    {
        return $this->type;
    }

    /**
     * Get the import section the ImportEntitiy belongs to
     *
     * @return string
     */
    public function getImportSection()
    {
        return QUI\CmsImport\Import::IMPORT_SECTION_PERMISSIONS;
    }
}
