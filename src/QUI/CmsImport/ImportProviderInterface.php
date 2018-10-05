<?php

namespace QUI\CmsImport;

use QUI\Projects\Project;

/**
 * Interface ImportProviderInterface
 *
 * Interface for all providers that collect and provide data for a QUIQQER CMS Import
 */
interface ImportProviderInterface
{
    /**
     * Get all QUIQQER projects that should be imported
     *
     * @return Project[]
     */
    public function getProjects();

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


}
