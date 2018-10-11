<?php

namespace QUI\CmsImport;

use QUI\CmsImport\Entities\ImportProject;
use QUI\CmsImport\Entities\ImportSite;

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
     * @param string $identifier - A unique ImportSite identifier
     * @param string $project - Project name
     * @param string $lang - Project lang
     * @return ImportSite
     */
    public function getSite($identifier, $project, $lang);

    /**
     * Get the complete hierarchical site structure as an associative array
     * with unique ImportSite identifiers
     *
     * @param string $project - Project name
     * @param string $lang - Project lang
     * @return array
     */
    public function getSiteHierarchy($project, $lang);

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
     * The purpose of this method is to prompt the user for necessary configuration data (like paths, DB credentials...)
     * regarding the system the import data is pulled from.
     *
     * If the ImportProvider collects its configuration in a different manner (e.g. a config file) this method
     * does not have to actually do anything.
     *
     * @return void
     */
    public function promptForConfig();
}
