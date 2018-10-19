<?php

namespace QUI\CmsImport;

use QUI\CmsImport\Entities\ImportGroup;
use QUI\CmsImport\Entities\ImportMediaItem;
use QUI\CmsImport\Entities\ImportProject;
use QUI\CmsImport\Entities\ImportSite;
use QUI\CmsImport\Entities\ImportTranslation;
use QUI\CmsImport\Entities\ImportUser;
use QUI\CmsImport\Hierarchy\GroupHierarchy;
use QUI\CmsImport\Hierarchy\MediaFolderHierarchy;
use QUI\CmsImport\Hierarchy\MediaItemHierarchy;
use QUI\CmsImport\Hierarchy\SiteHierarchy;
use QUI\CmsImport\ItemList\UserList;

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
     * @return ImportProject[]
     */
    public function getProjects();

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
     * @return MediaItemHierarchy
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
     * Get complete group hiearchy for QUIQQER group structure
     *
     * @return GroupHierarchy
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
     * @return UserList
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
     * Get all tags
     *
     * @param string $projectIdentifier - A unique ImportProject identifier
     * @param string $lang - Project lang
     * @return array - "tag title" => ['description' => "description"]
     */
    public function getTags($projectIdentifier, $lang);

    /**
     * Get all tag groups
     *
     * @param string $projectIdentifier - A unique ImportProject identifier
     * @param string $lang - Project lang
     * @return array - Associative array ("tag group title" => ['description' => "description", 'tags' => [array of associated tag titles])
     */
    public function getTagGroups($projectIdentifier, $lang);

    /**
     * Get all QUIQQER system languages that should be imported
     *
     * @return string[]
     */
    public function getSystemLanguages();

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
     * @return ImportTranslation[]
     */
    public function getTranslations($projectIdentifier);

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
