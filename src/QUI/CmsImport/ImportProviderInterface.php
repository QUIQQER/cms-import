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
     * Get all QUIQQER sites that should be imported (for a project)
     *
     * @param string $project - Project name
     * @return ImportSite[]
     */
    public function getSites($project);

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
     * This method is called by the main Import Console Tool before the actual Import is started
     *
     * Its purpose is to prompt the user for necessary configuration data (like paths, DB credentials...)
     * regarding the system the the import data is pulled from.
     *
     * If the ImportProvider collects its configuration in a different manner (e.g. a config file) this method
     * does not have to actually do anything.
     *
     * @param Console $Console
     * @return void
     */
    public function promptForConfig(Console $Console);
}
