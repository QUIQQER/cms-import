<?php

namespace QUI\CmsImport;

use QUI\CmsImport\Entities\ImportGroup;
use QUI\CmsImport\Entities\ImportMediaItem;
use QUI\CmsImport\Entities\ImportPermission;
use QUI\CmsImport\Entities\ImportProject;
use QUI\CmsImport\Entities\ImportSite;
use QUI\CmsImport\Entities\ImportTag;
use QUI\CmsImport\Entities\ImportTagGroup;
use QUI\CmsImport\Entities\ImportTranslation;
use QUI\CmsImport\Entities\ImportUser;
use QUI\CmsImport\MetaEntities\MetaHierarchy;
use QUI\CmsImport\MetaEntities\ProjectList;
use QUI\CmsImport\MetaEntities\SiteHierarchy;
use QUI\CmsImport\MetaEntities\MetaList;
use QUI\CmsImport\Provider\PMS\ImportProvider;


/**
 * Interface ImportProviderInterface
 *
 * Interface for all providers that collect and provide data for a QUIQQER CMS Import
 */
interface ImportProviderInterface
{
    /**
     * ImportProviderInterface constructor.
     *
     * @param Console $ImportConsole - The main QUIQQER Import console tool
     */
    public function __construct(Console $ImportConsole);

    /**
     * Get title of this Import Provider
     *
     * @return string
     */
    public function getTitle();

    /**
     * Get description of this Import Provider
     *
     * @return string
     */
    public function getDescription();

    /**
     * Get all QUIQQER projects that should be imported
     *
     * @return ProjectList
     */
    public function getProjectList();

    /**
     * Get an ImportProject
     *
     * @param int|string $projectIdentifier - Unique ImportProject identifier
     * @return ImportProject
     */
    public function getProject($projectIdentifier);

    /**
     * Get a site that should be imported
     *
     * @param string $siteIdentifier - A unique ImportSite identifier
     * @param string $projectIdentifier - A unique ImportProject identifier
     * @param string $lang - Project lang
     * @return ImportSite
     */
    public function getSite($siteIdentifier, $projectIdentifier, $lang);

    /**
     * Get the complete hierarchical site structure (structure only, not actual site data!)
     *
     * @param string $projectIdentifier - A unique ImportProject identifier
     * @param string $lang - Project lang
     * @return SiteHierarchy
     */
    public function getSiteHierarchy($projectIdentifier, $lang);

    /**
     * Get the complete hierarchical media item structure (folders, files, images)
     *
     * @param $projectIdentifier
     * @return MetaHierarchy
     */
    public function getMediaHierarchy($projectIdentifier);

    /**
     * Get an ImportMediaItem
     *
     * @param string|int $mediaItemIdentifier
     * @param string|int $projectIdentifier
     * @return ImportMediaItem
     */
    public function getMediaItem($mediaItemIdentifier, $projectIdentifier);

    /**
     * Get complete group hierarchy for QUIQQER group structure
     *
     * @return MetaHierarchy
     */
    public function getGroupHierarchy();

    /**
     * Get ImportGroup by identifier
     *
     * @param string|int $groupIdentifier
     * @return ImportGroup
     */
    public function getGroup($groupIdentifier);

    /**
     * Get complete list of QUIQQER users (meta-list, not actual User objects)
     *
     * @return MetaList
     */
    public function getUserList();

    /**
     * Get ImportUser by identifier
     *
     * @param string|int $userIdentifier
     * @return ImportUser
     */
    public function getUser($userIdentifier);

    /**
     * Get complete TagGroupHierarchy for a project
     *
     * @param string $projectIdentifier - A unique ImportProject identifier
     * @param string $lang - Project lang
     * @return MetaHierarchy
     */
    public function getTagGroupHierarchy($projectIdentifier, $lang);

    /**
     * Get an ImportTagGroup
     *
     * @param string|int $identifier - Unique ImportTagGroup identifier
     * @param string $projectIdentifier - A unique ImportProject identifier
     * @param string $lang - Project lang
     * @return ImportTagGroup
     */
    public function getTagGroup($identifier, $projectIdentifier, $lang);

    /**
     * Get complete list of ImportTag identifiers
     *
     * @param string $projectIdentifier - A unique ImportProject identifier
     * @param string $lang - Project lang
     * @return MetaList
     */
    public function getTagList($projectIdentifier, $lang);

    /**
     * Get an ImportTag
     *
     * @param string|int $identifier - Unique ImportTagGroup identifier
     * @param string $projectIdentifier - A unique ImportProject identifier
     * @param string $lang - Project lang
     * @return ImportTag
     */
    public function getTag($identifier, $projectIdentifier, $lang);

    /**
     * Get QUIQQER system configuration (as associative array)
     *
     * These are the settings for etc/conf.ini.php
     *
     * @return array
     */
    public function getSystemConfig();

    /**
     * Get all translations for a project
     *
     * @param int|string $projectIdentifier - Unique ImportProject identifier
     * @return MetaList
     */
    public function getTranslationList($projectIdentifier);

    /**
     * Get an ImportTranslation
     *
     * @param int|string $translationIdentifier
     * @param int|string $projectIdentifier
     * @return ImportTranslation
     */
    public function getTranslation($translationIdentifier, $projectIdentifier);

    /**
     * Get all permissions that shall be imported
     *
     * @return MetaList
     */
    public function getPermissionList();

    /**
     * Get an ImportPermission
     *
     * @param int|string $permissionIdentifier
     * @return ImportPermission
     */
    public function getPermission($permissionIdentifier);

    /**
     * The purpose of this method is to prompt the user for necessary configuration data (like paths, DB credentials...)
     * regarding the system the import data is pulled from.
     *
     * If the ImportProvider collects its configuration in a different manner (e.g. a config file) this method
     * does not have to actually do anything.
     *
     * @return void
     */
    public function promptForConfig();

    /**
     * Set instance of QUI\CmsImport\Import that is orchestrating the current import process
     *
     * @param Import $Import
     * @return void
     */
    public function setImport(Import $Import);

    /**
     * Get instance of QUI\CmsImport\Import that is orchestrating the current import process
     *
     * @return Import
     */
    public function getImport();
}
