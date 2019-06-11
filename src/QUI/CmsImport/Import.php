<?php

namespace QUI\CmsImport;

use QUI;
use QUI\Tags\Manager as TagManager;
use QUI\Tags\Groups\Handler as TagGroupManager;
use QUI\CmsImport\Entities\ImportSite;
use QUI\Cron\Manager as CronManager;
use QUI\CmsImport\MetaEntities\SiteEntity;
use QUI\Permissions\Manager as PermissionManager;
use QUI\CmsImport\Entities\ImportPermission;
use QUI\CmsImport\MetaEntities\MetaEntity;
use QUI\CmsImport\MetaEntities\MetaList;
use QUI\CmsImport\MetaEntities\ChildrenInterface;

/**
 * Class Import
 *
 * Imports data and structure from a Import provider to the current QUIQQER system
 */
class Import extends QUI\QDOM
{
    /**
     * Import sections
     */
    const IMPORT_SECTION_GENERAL      = 'general';
    const IMPORT_SECTION_SITES        = 'sites';
    const IMPORT_SECTION_MEDIA        = 'media';
    const IMPORT_SECTION_USERS        = 'users';
    const IMPORT_SECTION_GROUPS       = 'groups';
    const IMPORT_SECTION_PERMISSIONS  = 'permissions';
    const IMPORT_SECTION_PROJECTS     = 'projects';
    const IMPORT_SECTION_TRANSLATIONS = 'translations';
    const IMPORT_SECTION_TAGS         = 'tags';

    const ENTITY_ATTRIBUTE_QUIQQER_ID = 'quiqqerId';

    const TAGS_UNGROUPED = 'quiqqer_cms_import_ungrouped_tags';

    /**
     * This is used for (optional) output
     *
     * @var Console
     */
    protected $ConsoleTool = null;

    /**
     * The ImportProvider that provides import data
     *
     * @var ImportProviderInterface
     */
    protected $ImportProvider;

    /**
     * Collection of all relevant data that was successfully imported
     *
     * @var array
     */
    protected $importData = [];

    /**
     * @var array ['package' => isInstalled]
     */
    protected $quiqqerPackages = [
        'quiqqer/tags' => false
    ];

    /**
     * Collection of review messages
     *
     * @var array
     */
    protected $reviewFlags = [];

    /**
     * Collection of Import entities that have to be manually reviewed after the import process
     *
     * @var QUI\CmsImport\Entities\AbstractImportEntity[]
     */
    protected $reviewEntities = [];

    /**
     * Var dir of quiqqer/cms-import
     *
     * @var string
     */
    protected $varDir;

    /**
     * Import constructor.
     *
     * @param ImportProviderInterface $ImportProvider
     * @param array $settings
     * @throws QUI\Exception
     */
    public function __construct(ImportProviderInterface $ImportProvider, $settings = [])
    {
        $this->setAttributes([
            'cleanup'            => false,
            'importTags'         => false,
            'importSites'        => false,
            'importMedia'        => false,
            'importUsers'        => false,
            'importGroups'       => false,
            'importSystemConfig' => false,
            'importTranslations' => false,
            'importPermissions'  => false
        ]);

        $this->setAttributes($settings);
        $this->ImportProvider = $ImportProvider;
        $this->ImportProvider->setImport($this);

        $PackageManager = QUI::getPackageManager();

        foreach ($this->quiqqerPackages as $package => $isInstalled) {
            $this->quiqqerPackages[$package] = $PackageManager->isInstalled($package);
        }

        $this->varDir = QUI::getPackage('quiqqer/cms-import')->getVarDir();
    }

    /**
     * Start import process
     *
     * @throws QUI\Exception
     * @throws QUI\Users\Exception
     */
    public function start()
    {
        // Cleanup
        if ($this->getAttribute('cleanup')) {
            $this->cleanUpSystem();
        }

        // ImportProvider config
        $this->ImportProvider->promptForConfig();

        // Projects
        try {
            $this->importProjects();
        } catch (\Exception $Exception) {
            $this->writeWarning(
                'error.import_projects',
                [
                    'error' => $Exception->getMessage()
                ]
            );
        }

        // Translations
        if ($this->getAttribute('importTranslations')) {
            try {
                $this->importTranslations();
            } catch (\Exception $Exception) {
                $this->writeWarning(
                    'error.import_translations',
                    [
                        'error' => $Exception->getMessage()
                    ]
                );
            }
        }

        // Delete old standard project
        if ($this->getAttribute('cleanup')) {
            $this->writeHeader('delete_old_standard_project');

            try {
                $Projects   = QUI::getProjectManager();
                $OldProject = $Projects->getProject('old_standard_project');
                $Projects->deleteProject($OldProject);
            } catch (\Exception $Exception) {
                $this->writeWarning(
                    'error.delete_old_standard_project',
                    [
                        'error' => $Exception->getMessage()
                    ]
                );
            }
        }

        // Tags / tag groups
        if ($this->getAttribute('importTags')) {
            $this->writeHeader('importTags');

            if (QUI::getPackageManager()->isInstalled('quiqqer/tags')) {
                try {
                    $this->importTagGroups();
                    $this->importTags();
                } catch (\Exception $Exception) {
                    $this->writeWarning(
                        'error.import_tags',
                        [
                            'error' => $Exception->getMessage()
                        ]
                    );
                }
            } else {
                $this->writeWarning('tags_package_not_installed');
            }
        }

        // Permissions
        if ($this->getAttribute('importPermissions')) {
            try {
                $this->importPermissions();
            } catch (\Exception $Exception) {
                $this->writeWarning(
                    'error.import_permissions',
                    [
                        'error' => $Exception->getMessage()
                    ]
                );
            }
        }

        // Groups
        if ($this->getAttribute('importGroups')) {
            try {
                $this->importGroups();
            } catch (\Exception $Exception) {
                $this->writeWarning(
                    'error.import_groups',
                    [
                        'error' => $Exception->getMessage()
                    ]
                );
            }
        }

        // Users
        if ($this->getAttribute('importUsers')) {
            try {
                $this->importUsers();
            } catch (\Exception $Exception) {
                $this->writeWarning(
                    'error.import_users',
                    [
                        'error' => $Exception->getMessage()
                    ]
                );
            }
        }

        // Sites
        if ($this->getAttribute('importSites')) {
            try {
                $this->importSites();
            } catch (\Exception $Exception) {
                $this->writeWarning(
                    'error.import_sites',
                    [
                        'error' => $Exception->getMessage()
                    ]
                );
            }
        }

        // Media
        if ($this->getAttribute('importMedia')) {
            try {
                $this->importMedia();
            } catch (\Exception $Exception) {
                $this->writeWarning(
                    'error.import_media',
                    [
                        'error' => $Exception->getMessage()
                    ]
                );
            }
        }

        // System config
        if ($this->getAttribute('importSystemConfig')) {
            try {
                $this->importSystemConfig();
            } catch (\Exception $Exception) {
                $this->writeWarning(
                    'error.import_system_config',
                    [
                        'error' => $Exception->getMessage()
                    ]
                );
            }
        }

        $this->writeReviewLog();
    }

    /**
     * Start project import
     *
     * @return void
     */
    protected function importProjects()
    {
        $ProjectList = $this->ImportProvider->getProjectList();
        $Projects    = QUI::getProjectManager();

        $this->importData['projects'] = [];

        /** @var QUI\CmsImport\MetaEntities\ProjectEntity $ProjectEntity */
        foreach ($ProjectList->walkChildren() as $ProjectEntity) {
            $ImportProject = $this->ImportProvider->getProject($ProjectEntity->getId());

            $this->writeHeader('project', ['project' => $ImportProject->getName()]);

            if ($ImportProject->hasReviewFlags()) {
                $this->reviewEntities[] = $ImportProject;
            }

            try {
                // this is a badfix! QUIQQER caches the content of the main conf file
                // and at this point may have old config data; this forces QUIQQER to reload
                // the config from the filesystem.
                QUI::$Configs = [];

                $this->deleteProjectTables($ImportProject->getName(), $ProjectEntity->getLanguages());

                $NewProject = $Projects->createProject(
                    $ImportProject->getName(),
                    $ProjectEntity->getDefaultLanguage(),
                    $ProjectEntity->getLanguages()
                );

                $Projects->setConfigForProject($NewProject->getName(), $ImportProject->getAttributes());
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);

                $this->writeError('project_create_error', [
                    'error' => $Exception->getMessage()
                ], $ImportProject);

                continue;
            }

            // Cache imported project
            $this->importData['projects'][$ImportProject->getName()] = $NewProject;

            $this->writeInfo('project.success', ['project' => $NewProject->getName()]);
        }
    }

    /**
     * Delete project tables
     *
     * This is used to delete project tables that could not be deleted during
     * a regular cleanup.
     *
     * This may become necessary if an old import process abruptly exited and project
     * tables still exist in the database but the project itself in not listed in the
     * projects.ini.php
     *
     * @param string $project
     * @param array $langs
     * @return void
     */
    protected function deleteProjectTables($project, $langs)
    {
        $prefix = QUI_DB_PRFX.$project.'_';
        $tables = QUI::getDataBase()->table()->getTables();

        foreach ($tables as $tbl) {
            foreach ($langs as $lang) {
                $tblPrefix = $prefix.$lang.'_';

                if (mb_strpos($tbl, $tblPrefix) === 0) {
                    QUI::getDataBase()->table()->delete($tbl);
                }
            }
        }
    }

    /**
     * Import tag groups
     *
     * @return void
     */
    protected function importTagGroups()
    {
        if (!isset($this->importData['tagGroups'])) {
            $this->importData['tagGroups'] = [];
        }

        /**
         * @var string $projectIdentifier
         * @var QUI\Projects\Project $QuiqqerProject
         */
        foreach ($this->importData['projects'] as $projectIdentifier => $QuiqqerProject) {
            $project = $QuiqqerProject->getName();

            $this->importData['tagGroups'][$project] = [];

            foreach ($QuiqqerProject->getLanguages() as $lang) {
                $this->writeHeader('project_tag_groups', [
                    'projectIdentifier' => $projectIdentifier,
                    'lang'              => $lang
                ]);

                try {
                    $TagProject = QUI::getProject($project, $lang);
                } catch (\Exception $Exception) {
                    $this->writeError('quiqqer_project_error', [
                        'project' => $project,
                        'lang'    => $lang,
                        'error'   => $Exception->getMessage()
                    ]);

                    continue;
                }

                $this->importData['tagGroups'][$project][$lang] = [];

                $this->createTagGroups(
                    $TagProject,
                    $projectIdentifier,
                    $this->ImportProvider->getTagGroupHierarchy($projectIdentifier, $lang)
                );
            }
        }
    }

    /**
     * Import tags
     *
     * @return void
     */
    protected function importTags()
    {
        $this->importData['tags'] = [];

        /**
         * @var string $projectIdentifier
         * @var QUI\Projects\Project $QuiqqerProject
         */
        foreach ($this->importData['projects'] as $projectIdentifier => $QuiqqerProject) {
            $project = $QuiqqerProject->getName();

            $this->importData['tag'][$project] = [];

            foreach ($QuiqqerProject->getLanguages() as $lang) {
                $this->writeHeader('project_tags', [
                    'projectIdentifier' => $projectIdentifier,
                    'lang'              => $lang
                ]);

                $project = $QuiqqerProject->getName();

                try {
                    $TagProject = QUI::getProject($project, $lang);
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('tag_project_build', [
                        'project' => $project,
                        'lang'    => $lang,
                        'error'   => $Exception->getMessage()
                    ]);

                    continue;
                }

                $this->importData['tag'][$project][$lang] = [];

                $this->createTags(
                    $TagProject,
                    $projectIdentifier,
                    $this->ImportProvider->getTagList($projectIdentifier, $lang)
                );
            }
        }
    }

    /**
     * Start import of sites
     *
     * @throws QUI\Exception
     * @return void
     */
    protected function importSites()
    {
        $this->writeHeader('sites_start');

        $Projects = QUI::getProjectManager();

        /** @var QUI\Projects\Project $QuiqqerProject */
        foreach ($this->importData['projects'] as $projectIdentifier => $QuiqqerProject) {
            $langs          = $QuiqqerProject->getLanguages();
            $quiqqerProject = $QuiqqerProject->getName();
            $importedSites  = [];

            // Create sites
            foreach ($langs as $lang) {
                $TargetProject = $Projects->getProject($quiqqerProject, $lang);
                $SiteHierarchy = $this->ImportProvider->getSiteHierarchy($projectIdentifier, $lang);

                $importedSites[$lang] = $this->createSites($TargetProject, $projectIdentifier, $SiteHierarchy);
                $this->createSiteLinks($TargetProject, $SiteHierarchy, $importedSites[$lang]);
            }

            // Create language links for $lang
            foreach ($importedSites as $lang => $importSites) {
                $SourceProject = $Projects->getProject($quiqqerProject, $lang);

                foreach ($importSites as $quiqqerSiteId => $siteIdentifier) {
                    $ImportSite  = $this->ImportProvider->getSite($siteIdentifier, $projectIdentifier, $lang);
                    $QuiqqerSite = new QUI\Projects\Site\Edit($SourceProject, $quiqqerSiteId);

                    $this->writeInfo('site_create_lang_links', [
                        'siteId'    => $quiqqerSiteId,
                        'siteTitle' => $QuiqqerSite->getAttribute('title')
                    ]);

                    foreach ($ImportSite->getLanguageLinks() as $targetLang => $linkedSiteIdentifier) {
                        // Do not create langauge links for languages that have not been imported
                        // for the project
                        if (!in_array($targetLang, $langs)) {
                            continue;
                        }

                        $TargetProject     = $Projects->getProject($quiqqerProject, $targetLang);
                        $quiqqerLinkSiteId = array_search($linkedSiteIdentifier, $importedSites[$targetLang]);

                        if (empty($quiqqerLinkSiteId)) {
                            $this->writeWarning('site_lang_links_site_not_found', [
                                'siteIdentifier' => $linkedSiteIdentifier,
                                'targetLang'     => $targetLang
                            ], $ImportSite);

                            continue;
                        }

                        $LinkQuiqqerSite = new QUI\Projects\Site\Edit($TargetProject, $quiqqerLinkSiteId);
                        $QuiqqerSite->addLanguageLink($targetLang, $LinkQuiqqerSite->getId());
                    }
                }
            }
        }
    }

    /**
     * Start complete media structure
     *
     * @return void
     */
    protected function importMedia()
    {
        $this->writeHeader('media_start');

        /** @var QUI\Projects\Project $QuiqqerProject */
        foreach ($this->importData['projects'] as $projectIdentifier => $QuiqqerProject) {
            // Create media items
            $MediaHierarchy = $this->ImportProvider->getMediaHierarchy($projectIdentifier);
            $this->createMedia($QuiqqerProject, $projectIdentifier, $MediaHierarchy);
        }
    }

    /**
     * Import QUIQQER groups
     *
     * @throws QUI\Exception
     * @return void
     */
    protected function importGroups()
    {
        $this->writeHeader('groups_start');

        $this->importData['groups'] = [];

        $GroupHierarchy = $this->ImportProvider->getGroupHierarchy();
        $this->createGroups($GroupHierarchy);
    }

    /**
     * Import QUIQQER users
     *
     * @return void
     */
    protected function importUsers()
    {
        $this->writeHeader('users_start');

        $this->importData['users'] = [];

        $UserList = $this->ImportProvider->getUserList();
        $this->createUsers($UserList);
    }

    /**
     * Import QUIQQER system config (etc/conf.ini.php)
     *
     * @return void
     */
    protected function importSystemConfig()
    {
        $this->writeHeader('system_config_start');

        $quiqqerConfigFile = CMS_DIR.'etc/conf.ini.php';

        $prompt = $this->ConsoleTool->writePrompt(
            QUI::getLocale()->get(
                'quiqqer/cms-import',
                'prompt.system_config.backup',
                [
                    'configFile' => $quiqqerConfigFile
                ]
            ),
            'y'
        );

        if (mb_strtolower($prompt) !== 'n') {
            $backupFile = $quiqqerConfigFile.'.bak';

            $this->writeInfo('system_config_backup', [
                'backupFile' => $backupFile
            ]);

            if (file_exists($backupFile)) {
                unlink($backupFile);
            }

            copy($quiqqerConfigFile, $backupFile);
        }

        $config = $this->ImportProvider->getSystemConfig();

        try {
            $QuiqqerConf = QUI::getConfig('etc/conf.ini.php');

            foreach ($config as $section => $settings) {
                foreach ($settings as $k => $v) {
                    $this->writeInfo('system_config_entry', [
                        'section' => $section,
                        'key'     => $k,
                        'value'   => $v
                    ]);

                    $QuiqqerConf->set($section, $k, $v);
                }
            }

            $QuiqqerConf->save();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Import translations for all projects
     *
     * @return void
     */
    protected function importTranslations()
    {
        /** @var QUI\Projects\Project $QuiqqerProject */
        foreach ($this->importData['projects'] as $projectIdentifier => $QuiqqerProject) {
            $TranslationList = $this->ImportProvider->getTranslationList($projectIdentifier);
            $group           = 'project/'.$QuiqqerProject->getName();

            /** @var MetaEntity $TranslationEntity */
            foreach ($TranslationList->walkChildren() as $TranslationEntity) {
                $ImportTranslation = $this->ImportProvider->getTranslation(
                    $TranslationEntity->getId(),
                    $projectIdentifier
                );

                $this->writeInfo('translation_start', [
                    'group' => $group,
                    'var'   => $ImportTranslation->getVar()
                ]);

                if ($ImportTranslation->hasReviewFlags()) {
                    $this->reviewEntities[] = $ImportTranslation;
                }

                $data = [
                    'package' => 'quiqqer/cms-import'
                ];

                foreach ($ImportTranslation->getTranslations() as $lang => $text) {
                    $data[$lang] = $text;
                }

                try {
                    $translationVar = QUI\Translator::get($group, $ImportTranslation->getVar());

                    if (empty($translationVar)) {
                        QUI\Translator::addUserVar($group, $ImportTranslation->getVar(), $data);
                    } else {
                        QUI\Translator::edit($group, $ImportTranslation->getVar(), $data['package'], $data);
                    }
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('translation_create', [
                        'var'   => $ImportTranslation->getVar(),
                        'error' => $Exception->getMessage()
                    ], $ImportTranslation);

                    continue;
                }

                $this->writeInfo('translation_finish');
            }

            $this->writeInfo('translations_publish', [
                'group' => $group
            ]);

            try {
                QUI\Translator::publish($group);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);

                $this->writeError('translations_publish', [
                    'group' => $group,
                    'error' => $Exception->getMessage()
                ]);
            }
        }
    }

    /**
     * Import QUIQQER permissions
     *
     * @return void
     */
    protected function importPermissions()
    {
        $this->writeHeader('permissions_start');
        $this->createPermissions($this->ImportProvider->getPermissionList());
    }

    /**
     * Create tag groups
     *
     * @param QUI\Projects\Project $QuiqqerProject - The target QUIQQER project
     * @param string|int $projectIdentifier - Unique project identifier for the ImportProject
     * @param ChildrenInterface $TagGroupTree
     */
    protected function createTagGroups(
        QUI\Projects\Project $QuiqqerProject,
        $projectIdentifier,
        ChildrenInterface $TagGroupTree
    ) {
        $lang    = $QuiqqerProject->getLang();
        $project = $QuiqqerProject->getName();

        /** @var MetaEntity $TagGroupItem */
        foreach ($TagGroupTree->walkChildren() as $TagGroupItem) {
            $tagGroupIdentifier = $TagGroupItem->getId();
            $ImportTagGroup     = $this->ImportProvider->getTagGroup($tagGroupIdentifier, $projectIdentifier, $lang);
            $tagGroup           = $ImportTagGroup->getTitle();

            if ($ImportTagGroup->hasReviewFlags()) {
                $this->reviewEntities[] = $ImportTagGroup;
            }

            $this->writeInfo('project_tag_group', [
                'tagGroupTitle' => $tagGroup
            ]);

            $TagGroup = TagGroupManager::create($QuiqqerProject, $tagGroup);
            $TagGroup->setGenerator('quiqqer/cms-import');
            $TagGroup->setGenerateStatus(true);

            if ($ImportTagGroup->getPriority()) {
                $TagGroup->setPriority($ImportTagGroup->getPriority());
            }

            if ($ImportTagGroup->getDescription()) {
                $TagGroup->setDescription($ImportTagGroup->getDescription());
            }

            $ImportTagGroup->setAttribute(self::ENTITY_ATTRIBUTE_QUIQQER_ID, $TagGroup->getId());

            $parentId = $TagGroupItem->getParentId();

            if (!empty($parentId) && isset($this->importData['tagGroups'][$project][$lang][$parentId])) {
                try {
                    $TagGroup->setParentGroup($this->importData['tagGroups'][$project][$lang][$parentId]);
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('tags_set_parent_error', [
                        'error' => $Exception->getMessage()
                    ], $ImportTagGroup);
                }
            }

            $TagGroup->save();

            $this->importData['tagGroups'][$project][$lang][$tagGroupIdentifier] = $TagGroup->getId();

            if ($TagGroupItem->hasChildren()) {
                $this->createTagGroups($QuiqqerProject, $projectIdentifier, $TagGroupItem);
            }
        }
    }

    /**
     * Create tags
     *
     * @param QUI\Projects\Project $QuiqqerProject - The target QUIQQER project
     * @param string|int $projectIdentifier - Unique project identifier for the ImportProject
     * @param ChildrenInterface $TagList
     */
    protected function createTags(
        QUI\Projects\Project $QuiqqerProject,
        $projectIdentifier,
        ChildrenInterface $TagList
    ) {
        $project    = $QuiqqerProject->getName();
        $lang       = $QuiqqerProject->getLang();
        $TagManager = new TagManager($QuiqqerProject);

        /** @var MetaEntity $TagItem */
        foreach ($TagList->walkChildren() as $TagItem) {
            $tagIdentifier = $TagItem->getId();
            $ImportTag     = $this->ImportProvider->getTag($tagIdentifier, $projectIdentifier, $lang);
            $tag           = $ImportTag->getTitle();

            if ($ImportTag->hasReviewFlags()) {
                $this->reviewEntities[] = $ImportTag;
            }

            $this->writeInfo('project_tag', [
                'tagTitle' => $tag
            ]);

            try {
                if ($TagManager->existsTagTitle($tag)) {
                    $this->writeWarning(
                        'project_tag_duplicate_title',
                        [
                            'tag'     => $tag,
                            'newTag'  => $tag.'-1',
                            'project' => $QuiqqerProject->getName(),
                            'lang'    => $lang
                        ],
                        $ImportTag
                    );

                    $tag .= '-1';
                }

                $quiqqerTag = $TagManager->add($tag, [
                    'title'     => $tag,
                    'desc'      => $ImportTag->getDescription() ?: null,
                    'generator' => 'quiqqer/cms-import',
                    'generated' => true
                ]);

                $ImportTag->setAttribute(self::ENTITY_ATTRIBUTE_QUIQQER_ID, $quiqqerTag);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);

                $this->writeError('tag_create', [
                    'error' => $Exception->getMessage()
                ], $ImportTag);

                continue;
            }

            // Put tag in tag groups
            foreach ($ImportTag->getTagGroups() as $tagGroupIdentifier) {
                if (!empty($tagGroupIdentifier)
                    && isset($this->importData['tagGroups'][$project][$lang][$tagGroupIdentifier])) {
                    try {
                        $TagGroup = TagGroupManager::get(
                            $QuiqqerProject,
                            $this->importData['tagGroups'][$project][$lang][$tagGroupIdentifier]
                        );
                        $TagGroup->addTag($quiqqerTag);
                    } catch (\Exception $Exception) {
                        QUI\System\Log::writeException($Exception);

                        $this->writeError('tags_set_group_error', [
                            'tagGroupIdentifier' => $tagGroupIdentifier,
                            'error'              => $Exception->getMessage()
                        ], $ImportTag);

                        continue;
                    }

                    $TagGroup->save();
                }
            }

            $this->importData['tags'][$project][$lang][$tagIdentifier] = $quiqqerTag;
        }
    }

    /**
     * Create sites in the QUIQQER system
     *
     * @param QUI\Projects\Project $QuiqqerProject
     * @param string|int $projectIdentifier - Unique ImportProject identifier
     * @param ChildrenInterface $SiteTree
     * @param QUIQQERImportSite|null $RootQuiqqerSite
     * @param array &$importedSiteIds
     * @return array
     */
    protected function createSites(
        QUI\Projects\Project $QuiqqerProject,
        $projectIdentifier,
        ChildrenInterface $SiteTree,
        QUIQQERImportSite $RootQuiqqerSite = null,
        &$importedSiteIds = []
    ) {
        $lang     = $QuiqqerProject->getLang();
        $sitesTbl = QUI::getDBProjectTableName('sites', $QuiqqerProject);

        /** @var SiteEntity $ChildSiteItem */
        foreach ($SiteTree->walkChildren() as $ChildSiteItem) {
            // Site links are not created as actual sites but as links (created after all sites are created)
            if ($ChildSiteItem->isLink()) {
                continue;
            }

            $siteIdentifier       = $ChildSiteItem->getId();
            $ImportSite           = $this->ImportProvider->getSite($siteIdentifier, $projectIdentifier, $lang);
            $importQuiqqerSiteId  = $ImportSite->getQuiqqerId();
            $importSiteAttributes = $ImportSite->getAttributes();

            $this->writeInfo('site_start', [
                'siteIdentifier' => $siteIdentifier,
                'siteTitle'      => $ImportSite->getAttribute('title')
            ]);

            // Add to review pool
            if ($ImportSite->hasReviewFlags()) {
                $this->reviewEntities[] = $ImportSite;
            }

            // Check if site ID has already been imported
            if (!empty($importQuiqqerSiteId) && isset($importedSiteIds[$importQuiqqerSiteId])) {
                $this->writeWarning(
                    'site_duplicate_id',
                    [
                        'siteIdentifier' => $importQuiqqerSiteId,
                        'siteTitle'      => $ImportSite->getAttribute('title')
                    ],
                    $ImportSite
                );

                continue;
            }

            // Special case: QUIQQER root site
            if (empty($RootQuiqqerSite)) {
                try {
                    $RootQuiqqerSite = new QUIQQERImportSite($QuiqqerProject, 1);
                    $RootQuiqqerSite->setAttributes($importSiteAttributes);
                    $RootQuiqqerSite->save();
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('site_edit', [
                        'error' => $Exception->getMessage()
                    ], $ImportSite);

                    continue;
                }

                $NewSite = $RootQuiqqerSite;
            } else {
                $createAttributes = $importSiteAttributes;

                if (!empty($importQuiqqerSiteId)) {
                    $createAttributes['id'] = $importQuiqqerSiteId;
                }

                try {
                    $newSiteId = $RootQuiqqerSite->createChild($createAttributes);
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('site_create', [
                        'error' => $Exception->getMessage()
                    ], $ImportSite);

                    continue;
                }

                // Set all attributes to the site
                try {
                    $NewSite = new QUIQQERImportSite($QuiqqerProject, $newSiteId);
                    $NewSite->setAttributes($importSiteAttributes);
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('site_edit', [
                        'error' => $Exception->getMessage()
                    ], $ImportSite);

                    continue;
                }
            }

            try {
                $newSiteId = $NewSite->getId();
            } catch (\Exception $Exception) {
                // this cannot occurr, since getId() is called without a parameter
            }

            $ImportSite->setAttribute(self::ENTITY_ATTRIBUTE_QUIQQER_ID, $newSiteId);

            // Set c_date in database
            if (!empty($importSiteAttributes['c_date'])) {
                QUI::getDataBase()->update(
                    $sitesTbl,
                    [
                        'c_date' => $importSiteAttributes['c_date']
                    ],
                    [
                        'id' => $newSiteId
                    ]
                );
            }

            // Activate site
            if ($importSiteAttributes['active']) {
                try {
                    $NewSite->activate();
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('site_activate', [
                        'error' => $Exception->getMessage()
                    ], $ImportSite);
                }
            }

            // Import QUIQQER tags
            if ($this->getAttribute('importTags')) {
                $tags = $ImportSite->getTags();

                if ($this->quiqqerPackages['quiqqer/tags']) {
                    $this->importSiteTags($ImportSite, $NewSite);
                } elseif (!empty($tags)) {
                    $this->writeWarning('site_tags_plugin_missing');
                }
            }

            try {
                $NewSite->save();
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);

                $this->writeError('site_edit', [
                    'error' => $Exception->getMessage()
                ], $ImportSite);

                continue;
            }

            // Save
            $importedSiteIds[$newSiteId] = $siteIdentifier;

            $this->writeInfo('site_finish', [
                'siteIdentifier'   => $siteIdentifier,
                'quiqqerSiteId'    => $newSiteId,
                'quiqqerSiteTitle' => $NewSite->getAttribute('title')
            ]);

            if ($ChildSiteItem->hasChildren()) {
                $this->createSites($QuiqqerProject, $projectIdentifier, $ChildSiteItem, $NewSite, $importedSiteIds);
            }
        }

        return $importedSiteIds;
    }

    /**
     * @param QUI\Projects\Project $QuiqqerProject
     * @param ChildrenInterface $SiteTree
     * @param array $importSiteMap - Map of imported sites (quiqqer site id => siteIdentifier)
     * @return void
     * @throws QUI\Exception
     */
    protected function createSiteLinks(
        QUI\Projects\Project $QuiqqerProject,
        ChildrenInterface $SiteTree,
        $importSiteMap
    ) {
        /** @var SiteEntity $ChildSiteItem */
        foreach ($SiteTree->walkChildren() as $ChildSiteItem) {
            if (!$ChildSiteItem->isLink() || !$ChildSiteItem->getParentId()) {
                if ($ChildSiteItem->hasChildren()) {
                    $this->createSiteLinks($QuiqqerProject, $ChildSiteItem, $importSiteMap);
                }

                continue;
            }

            $quiqqerSiteIdentifier = $ChildSiteItem->getId();
            $linkSiteIdentifier    = $ChildSiteItem->getParentId();
            $childQuiqqerSiteId    = false;
            $linkQuiqqerSiteId     = false;

            foreach ($importSiteMap as $quiqqerSiteId => $siteIdentifier) {
                if ($siteIdentifier === $linkSiteIdentifier) {
                    $linkQuiqqerSiteId = $quiqqerSiteId;
                }

                if ($siteIdentifier === $quiqqerSiteIdentifier) {
                    $childQuiqqerSiteId = $quiqqerSiteId;
                }
            }

            if (!empty($linkQuiqqerSiteId) && !empty($childQuiqqerSiteId)) {
                $QuiqqerSite = new QUIQQERImportSite($QuiqqerProject, $childQuiqqerSiteId);
                $QuiqqerSite->linked($linkQuiqqerSiteId);

                $this->writeInfo('site_link', [
                    'sourceId'    => $QuiqqerSite->getId(),
                    'sourceTitle' => $QuiqqerSite->getAttribute('title'),
                    'targetId'    => $linkQuiqqerSiteId
                ]);
            }

            if ($ChildSiteItem->hasChildren()) {
                $this->createSiteLinks($QuiqqerProject, $ChildSiteItem, $importSiteMap);
            }
        }
    }

    /**
     * Import tags of an ImportSite to a QUIQQER Site
     *
     * @param ImportSite $ImportSite
     * @param QUI\Projects\Site\Edit $QuiqqerSite
     * @return void
     */
    protected function importSiteTags(ImportSite $ImportSite, QUI\Projects\Site\Edit $QuiqqerSite)
    {
        $QuiqqerProject = $QuiqqerSite->getProject();
        $project        = $QuiqqerProject->getName();
        $lang           = $QuiqqerProject->getLang();
        $TagManager     = new TagManager($QuiqqerProject);

        // Tags
        $this->writeInfo('site_tags_start');

        foreach ($ImportSite->getTags() as $tagIdentifier) {
            if (empty($this->importData['tags'][$project][$lang][$tagIdentifier])) {
                $this->writeWarning('site_tags_tag_not_found', [
                    'tagTitle' => $tagIdentifier
                ], $ImportSite);

                continue;
            }

            $quiqqerTag = $this->importData['tags'][$project][$lang][$tagIdentifier];

            if (!$TagManager->existsTag($quiqqerTag)) {
                $this->writeWarning('site_tags_tag_not_found', [
                    'tagTitle' => $quiqqerTag
                ], $ImportSite);

                continue;
            }

            try {
                $TagManager->addTagToSite($QuiqqerSite->getId(), $quiqqerTag);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // Tag Groups
        $this->writeInfo('site_tag_groups_start');

        foreach ($ImportSite->getTagGroups() as $tagGroupIdentifier) {
            if (empty($this->importData['tagGroups'][$project][$lang][$tagGroupIdentifier])) {
                $this->writeWarning('site_tags_tag_group_not_found', [
                    'tagGroupTitle' => $tagGroupIdentifier
                ], $ImportSite);

                continue;
            }

            $quiqqerTagGroupId = $this->importData['tagGroups'][$project][$lang][$tagGroupIdentifier];

            try {
                $groups = TagGroupManager::getGroups($QuiqqerProject, [
                    'where' => [
                        'id' => $quiqqerTagGroupId
                    ]
                ]);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);

                $this->writeError('tags_get_groups', [
                    'error' => $Exception->getMessage()
                ], $ImportSite);

                continue;
            }

            if (empty($groups)) {
                $this->writeWarning('site_tags_tag_group_not_found', [
                    'tagGroupTitle' => $tagGroupIdentifier
                ], $ImportSite);

                continue;
            }

            $siteTagGroups = $QuiqqerSite->getAttribute('quiqqer.tags.tagGroups');

            if (empty($siteTagGroups)) {
                $siteTagGroups = [];
            } else {
                $siteTagGroups = explode(',', $siteTagGroups);
            }

            /** @var QUI\Tags\Groups\Group $TagGroup */
            $TagGroup   = current($groups);
            $tagGroupId = $TagGroup->getId();

            if (in_array($tagGroupId, $siteTagGroups)) {
                continue;
            }

            $siteTagGroups[] = $tagGroupId;
            $QuiqqerSite->setAttribute('quiqqer.tags.tagGroups', implode(',', $siteTagGroups));
        }

        // Refresh site data
        try {
            $QuiqqerSite->load('quiqqer/tags');
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            $this->writeError('site_refresh', [
                'error' => $Exception->getMessage()
            ], $ImportSite);
        }
    }

    /**
     * Create media items/structure in the QUIQQER system
     *
     * @param QUI\Projects\Project $QuiqqerProject
     * @param string|int $projectIdentifier - Unique ImportProject identifier
     * @param ChildrenInterface $MediaTree
     * @param QUIQQERImportMediaFolder $RootQuiqqerMediaFolder
     * @param array &$importedMediaIds
     * @return array
     */
    protected function createMedia(
        QUI\Projects\Project $QuiqqerProject,
        $projectIdentifier,
        ChildrenInterface $MediaTree,
        QUIQQERImportMediaFolder $RootQuiqqerMediaFolder = null,
        &$importedMediaIds = []
    ) {
        if (empty($RootQuiqqerMediaFolder)) {
            $RootQuiqqerMediaFolder = $this->getQuiqqerImportMediaFolder(1, $QuiqqerProject);
        }

        /** @var MetaEntity $ChildMediaItem */
        foreach ($MediaTree->walkChildren() as $ChildMediaItem) {
            $mediaItemIdentifier   = $ChildMediaItem->getId();
            $ImportMediaItem       = $this->ImportProvider->getMediaItem($mediaItemIdentifier, $projectIdentifier);
            $importQuiqqerMediaId  = $ImportMediaItem->getQuiqqerId();
            $importMediaAttributes = $ImportMediaItem->getAttributes();

            if ($ImportMediaItem->hasReviewFlags()) {
                $this->reviewEntities[] = $ImportMediaItem;
            }

            if ($ImportMediaItem->isFolder()) {
                $this->writeInfo('media_folder_start', [
                    'identifier' => $mediaItemIdentifier,
                    'title'      => $ImportMediaItem->getTitle()
                ]);
            } else {
                $this->writeInfo('media_item_start', [
                    'identifier' => $mediaItemIdentifier,
                    'title'      => $ImportMediaItem->getTitle()
                ]);
            }

            // Check if site ID has already been imported
            if (!empty($importQuiqqerMediaId) && isset($importedMediaIds[$importQuiqqerMediaId])) {
                $this->writeWarning(
                    'media_duplicate_id',
                    [
                        'identifier' => $mediaItemIdentifier,
                        'title'      => $ImportMediaItem->getTitle()
                    ],
                    $ImportMediaItem
                );

                continue;
            }

            $newRootId = false;

            /**
             * $importQuiqqerMediaId may not be 1 because this is the QUIQQER Media root
             * folder ID (which can neither be changed nor deleted)
             */
            if ($importQuiqqerMediaId == 1) {
                $newRootId = 1;
            } else {
                if ($ImportMediaItem->isFolder()) {
                    try {
                        $NewItem = $RootQuiqqerMediaFolder->createFolder(
                            $ImportMediaItem->getTitle(),
                            $importQuiqqerMediaId ?: null
                        );
                    } catch (\Exception $Exception) {
                        QUI\System\Log::writeException($Exception);

                        $this->writeError('media_folder_create', [
                            'error' => $Exception->getMessage()
                        ], $ImportMediaItem);

                        continue;
                    }

                    try {
                        $NewItem->setAttributes($importMediaAttributes);
                        $NewItem->save();
                    } catch (\Exception $Exception) {
                        QUI\System\Log::writeException($Exception);

                        $this->writeError('media_folder_edit', [
                            'error' => $Exception->getMessage()
                        ], $ImportMediaItem);

                        continue;
                    }

                    // Activate media item
                    if ($importMediaAttributes['active']) {
                        try {
                            $NewItem->activate();
                        } catch (\Exception $Exception) {
                            QUI\System\Log::writeException($Exception);

                            $this->writeError('media_folder_activate', [
                                'error' => $Exception->getMessage()
                            ], $ImportMediaItem);
                        }
                    }

                    $newRootId = $NewItem->getId();
                } else {
                    $mediaFile = $ImportMediaItem->getFile();

                    // Check if file was given and/or exists first
                    if (empty($mediaFile)) {
                        $this->writeWarning('media_file_not_set', [
                            'identifier' => $mediaItemIdentifier
                        ], $ImportMediaItem);
                    } elseif (!file_exists($mediaFile)) {
                        $this->writeWarning('media_file_not_found', [
                            'identifier' => $mediaItemIdentifier,
                            'file'       => $mediaFile
                        ], $ImportMediaItem);
                    } else {
                        $mediaFileName         = basename($mediaFile);
                        $deleteFileAfterUpload = false;

                        if ($RootQuiqqerMediaFolder->fileWithNameExists($mediaFileName)) {
                            $filePrefix = '1-';

                            if (!empty($importQuiqqerMediaId)) {
                                $filePrefix = $importQuiqqerMediaId.'-';
                            }

                            copy($mediaFile, $this->varDir.$filePrefix.$mediaFileName);

                            $mediaFile             = $this->varDir.$filePrefix.$mediaFileName;
                            $deleteFileAfterUpload = true;

                            $this->writeWarning('media_file_rename', [
                                'originalFileName' => $mediaFileName,
                                'newFileName'      => $filePrefix.$mediaFileName
                            ], $ImportMediaItem);
                        }


                        try {
                            $NewItem = $RootQuiqqerMediaFolder->uploadFile(
                                $mediaFile,
                                QUIQQERImportMediaFolder::FILE_OVERWRITE_NONE,
                                $importQuiqqerMediaId ?: null
                            );
                        } catch (\Exception $Exception) {
                            QUI\System\Log::writeException($Exception);

                            $this->writeError('media_item_create', [
                                'error' => $Exception->getMessage()
                            ], $ImportMediaItem);

                            continue;
                        }

                        if ($deleteFileAfterUpload) {
                            unlink($mediaFile);
                        }

                        $importMediaAttributes['title'] = $ImportMediaItem->getTitle();

                        $NewItem->setAttributes($importMediaAttributes);

                        try {
                            $NewItem->save();
                        } catch (\Exception $Exception) {
                            QUI\System\Log::writeException($Exception);

                            $this->writeError('media_item_edit', [
                                'error' => $Exception->getMessage()
                            ], $ImportMediaItem);

                            continue;
                        }

                        // Activate media item
                        if ($importMediaAttributes['active']) {
                            try {
                                $NewItem->activate();
                            } catch (\Exception $Exception) {
                                QUI\System\Log::writeException($Exception);

                                $this->writeError('media_item_activate', [
                                    'error' => $Exception->getMessage()
                                ], $ImportMediaItem);
                            }
                        }

                        $newRootId = $NewItem->getId();
                    }
                }

                $ImportMediaItem->setAttribute(self::ENTITY_ATTRIBUTE_QUIQQER_ID, $newRootId);

                if ($newRootId && $ImportMediaItem->isFolder()) {
                    $this->writeInfo('media_folder_finish', [
                        'identifier'        => $mediaItemIdentifier,
                        'quiqqerMediaId'    => $NewItem->getId(),
                        'quiqqerMediaTitle' => $NewItem->getAttribute('title')
                    ]);
                } else {
                    $this->writeInfo('media_item_finish', [
                        'identifier'        => $mediaItemIdentifier,
                        'quiqqerMediaId'    => $NewItem->getId(),
                        'quiqqerMediaTitle' => $NewItem->getAttribute('title')
                    ]);
                }
            }

            // @todo c_date setzen
            // Set c_date in database
//            if (!empty($importSiteAttributes['c_date'])) {
//                QUI::getDataBase()->update(
//                    $sitesTbl,
//                    [
//                        'c_date' => $importSiteAttributes['c_date']
//                    ],
//                    [
//                        'id' => $newSiteId
//                    ]
//                );
//            }

            $importedMediaIds[$newRootId] = true;

            if ($ChildMediaItem->hasChildren()) {
                if ($ImportMediaItem->isFolder()) {
                    $this->createMedia(
                        $QuiqqerProject,
                        $projectIdentifier,
                        $ChildMediaItem,
                        $this->getQuiqqerImportMediaFolder($newRootId, $QuiqqerProject),
                        $importedMediaIds
                    );
                } else {
                    $this->writeWarning('media_file_cannot_have_children', [
                        'identifier' => $mediaItemIdentifier
                    ], $ImportMediaItem);
                }
            }
        }

        return $importedMediaIds;
    }

    /**
     * Create group hierarchy in the QUIQQER system
     *
     * @param ChildrenInterface $GroupTree
     * @param QUIQQERImportGroup $RootQuiqqerGroup
     * @return void
     */
    protected function createGroups(
        ChildrenInterface $GroupTree,
        QUIQQERImportGroup $RootQuiqqerGroup = null
    ) {
        $quiqqerRootGroupId = QUI::conf('globals', 'root');
        $Permission         = new PermissionManager();

        if (empty($RootQuiqqerGroup)) {
            try {
                $RootQuiqqerGroup = new QUIQQERImportGroup($quiqqerRootGroupId);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);

                $this->writeError('root_group_get', [
                    'error' => $Exception->getMessage()
                ]);

                return;
            }
        }

        /** @var MetaEntity $ChildGroupItem */
        foreach ($GroupTree->walkChildren() as $ChildGroupItem) {
            $groupIdentifier       = $ChildGroupItem->getId();
            $ImportGroup           = $this->ImportProvider->getGroup($groupIdentifier);
            $importQuiqqerGroupId  = $ImportGroup->getQuiqqerId();
            $importGroupAttributes = array_merge(
                $ImportGroup->getAttributes(),
                [
                    'name' => $ImportGroup->getName()
                ]
            );

            $this->writeInfo('group_start', [
                'identifier' => $groupIdentifier
            ]);

            if ($ImportGroup->hasReviewFlags()) {
                $this->reviewEntities[] = $ImportGroup;
            }

            // Check if group ID has already been imported
            if (!empty($importQuiqqerGroupId)
                && in_array($importQuiqqerGroupId, $this->importData['groups'])
            ) {
                $this->writeWarning(
                    'group_duplicate_id',
                    [
                        'identifier' => $groupIdentifier,
                        'id'         => $importQuiqqerGroupId
                    ],
                    $ImportGroup
                );

                continue;
            }

            if ($importQuiqqerGroupId == 1 && $RootQuiqqerGroup->getId() == $quiqqerRootGroupId) {
                $NewGroup = $RootQuiqqerGroup;
            } else {
                try {
                    $NewGroup = $RootQuiqqerGroup->createChild(
                        $ImportGroup->getName(),
                        null,
                        $importQuiqqerGroupId ?: null
                    );
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('group_create', [
                        'error' => $Exception->getMessage()
                    ], $ImportGroup);

                    continue;
                }
            }

            $ImportGroup->setAttribute(self::ENTITY_ATTRIBUTE_QUIQQER_ID, $NewGroup->getId());

            $NewGroup->setAttributes($importGroupAttributes);

            try {
                $NewGroup->save();
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);

                $this->writeError('group_edit', [
                    'error' => $Exception->getMessage()
                ], $ImportGroup);

                continue;
            }

            // Activate group
            if (!empty($importGroupAttributes['active'])) {
                try {
                    $NewGroup->activate();
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('group_activate', [
                        'error' => $Exception->getMessage()
                    ], $ImportGroup);
                }
            }

            // Set admin access permission
            if ($ImportGroup->hasAdminAccess()) {
                $groupPermissions                  = $Permission->getPermissions($NewGroup);
                $groupPermissions['quiqqer.admin'] = true;

                try {
                    $Permission->setPermissions($NewGroup, $groupPermissions);
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('group_set_permissions', [
                        'error' => $Exception->getMessage()
                    ], $ImportGroup);
                }
            }

            $this->importData['groups'][$groupIdentifier] = $NewGroup->getId();

            $this->writeInfo('group_finish', [
                'identifier'       => $groupIdentifier,
                'quiqqerGroupId'   => $NewGroup->getId(),
                'quiqqerGroupName' => $NewGroup->getName()
            ]);

            if ($ChildGroupItem->hasChildren()) {
                try {
                    $NewImportGroup = new QUIQQERImportGroup($NewGroup->getId());
                    $this->createGroups($ChildGroupItem, $NewImportGroup);
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('group_create', [
                        'error' => $Exception->getMessage()
                    ], $ImportGroup);
                }
            }
        }
    }

    /**
     * Create QUIQQER users from a UserList
     *
     * @param MetaList $UserList
     * @return void
     */
    protected function createUsers(MetaList $UserList)
    {
        $UserManager = new QUIQQERImportUserManager();
        $DB          = QUI::getDataBase();
        $usersTable  = QUI\Users\Manager::table();

        /** @var MetaEntity $UserItem */
        foreach ($UserList->walkChildren() as $UserItem) {
            $ImportUser = $this->ImportProvider->getUser($UserItem->getId());

            $this->writeInfo('user_start', [
                'identifier' => $ImportUser->getIdentifier(),
                'username'   => $ImportUser->getUsername()
            ]);

            if ($ImportUser->hasReviewFlags()) {
                $this->reviewEntities[] = $ImportUser;
            }

            try {
                $NewUser = $UserManager->createChild(
                    $ImportUser->getUsername(),
                    null,
                    $ImportUser->getQuiqqerId()
                );
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);

                $this->writeError('user_create', [
                    'error' => $Exception->getMessage()
                ], $ImportUser);

                continue;
            }

            $importUserAttributes = $ImportUser->getAttributes();

            if ($ImportUser->isSU()) {
                $importUserAttributes['su'] = true;
            }

            $NewUser->setAttributes($importUserAttributes);

            // Groups
            foreach ($ImportUser->getGroups() as $groupIdentifier) {
                if (!empty($this->importData['groups'][$groupIdentifier])) {
                    $NewUser->addToGroup($this->importData['groups'][$groupIdentifier]);
                }
            }

            // Set password hash to DB
            if ($ImportUser->getPasswordHash()) {
                $DB->update($usersTable, [
                    'password' => $ImportUser->getPasswordHash()
                ], [
                    'id' => $NewUser->getId()
                ]);
            } else {
                // auto-generate a random password
                try {
                    $this->writeInfo('user_generate_password');
                    $NewUser->setPassword(hash('sha256', random_bytes(128)));
                } catch (\Exception $Exception) {
                    $this->writeError('user_edit', [
                        'error' => $Exception->getMessage()
                    ], $ImportUser);
                }
            }

            $ImportUser->setAttribute(self::ENTITY_ATTRIBUTE_QUIQQER_ID, $NewUser->getId());

            try {
                $NewUser->save();
                $NewUser->refresh();
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);

                $this->writeError('user_edit', [
                    'error' => $Exception->getMessage()
                ], $ImportUser);

                continue;
            }

            // Activate
            if ($importUserAttributes['active']) {
                try {
                    $NewUser->activate();
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('user_activate', [
                        'error' => $Exception->getMessage()
                    ], $ImportUser);
                }
            }

            $this->writeInfo('user_finish', [
                'quiqqerUserId'   => $NewUser->getId(),
                'quiqqerUsername' => $NewUser->getUsername()
            ]);
        }
    }

    /**
     * Create QUIQQER permissions
     *
     * @param MetaList $PermissionList
     * @return void
     */
    protected function createPermissions($PermissionList)
    {
        $PermissionManager = QUI::getPermissionManager();

        /** @var MetaEntity $PermissionEntity */
        foreach ($PermissionList->walkChildren() as $PermissionEntity) {
            $ImportPermission = $this->ImportProvider->getPermission($PermissionEntity->getId());
            $permission       = $ImportPermission->getPermission();

            if ($ImportPermission->hasReviewFlags()) {
                $this->reviewEntities[] = $ImportPermission;
            }

            // Check if permission already exists
            try {
                $PermissionManager->getPermissionData($permission);
                continue;
            } catch (\Exception $Exception) {
                // Permission does not exist -> create it
            }

            // Permission translations (title)
            $translationsTitle = $ImportPermission->getTranslationsTitle();

            if (!empty($translationsTitle)) {
                $data = [
                    'package' => 'quiqqer/cms-import'
                ];

                foreach ($translationsTitle as $lang => $text) {
                    $data[$lang] = $text;
                }

                try {
                    QUI\Translator::addUserVar('cms-import/permissions', $permission.'.title', $data);
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('permission_translation', [
                        'error' => $Exception->getMessage()
                    ], $ImportPermission);
                }
            }

            $translationsDescription = $ImportPermission->getTranslationsDescription();

            // Permission translations (description)
            if (!empty($translationsDescription)) {
                $data = [
                    'package' => 'quiqqer/cms-import'
                ];

                foreach ($translationsDescription as $lang => $text) {
                    $data[$lang] = $text;
                }

                try {
                    QUI\Translator::addUserVar('cms-import/permissions', $permission.'.description', $data);
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);

                    $this->writeError('permission_translation', [
                        'error' => $Exception->getMessage()
                    ], $ImportPermission);
                }
            }

            $area = $ImportPermission->getPermissionArea();

            if ($area === ImportPermission::AREA_GLOBAL) {
                $area = '';
            }

            $PermissionManager->addPermission([
                'name'  => $permission,
                'type'  => $ImportPermission->getPermissionType(),
                'area'  => $area,
                'src'   => 'cms-import',
                'title' => 'cms-import/permissions '.$permission.'.title',
                'desc'  => 'cms-import/permissions '.$permission.'.description'
            ]);

            $ImportPermission->setAttribute(self::ENTITY_ATTRIBUTE_QUIQQER_ID, $permission);
        }
    }

    /**
     * Get special QUIQQERImportMediaFolder
     *
     * @param int $id
     * @param QUI\Projects\Project $Project
     * @return QUIQQERImportMediaFolder
     */
    protected function getQuiqqerImportMediaFolder($id, QUI\Projects\Project $Project)
    {
        $result = QUI::getDataBase()->fetch([
            'from'  => QUI::getDBProjectTableName('media', $Project, false),
            'where' => [
                'id' => $id
            ]
        ]);

        return new QUIQQERImportMediaFolder(current($result), $Project->getMedia());
    }

    /**
     * Set console tool for output purposes
     *
     * @param Console $ConsoleTool
     * @return void
     */
    public function setConsoleTool(Console $ConsoleTool)
    {
        $this->ConsoleTool = $ConsoleTool;
    }

    /**
     * Cleanup QUIQQER System
     *
     * @throws QUI\Exception
     * @throws QUI\Users\Exception
     */
    protected function cleanUpSystem()
    {
        $this->writeHeader('cleanup');

        // Delete all user except for the root user
        $this->writeInfo('cleanup.delete_users');

        $rootUserId = (int)QUI::conf('globals', 'rootuser');

        $results = QUI::getDataBase()->fetch([
            'select' => ['id'],
            'from'   => QUI::getDBTableName('users'),
            'where'  => [
                'id' => [
                    'type'  => 'NOT',
                    'value' => $rootUserId
                ]
            ]
        ]);

        $Users = QUI::getUsers();

        foreach ($results as $row) {
            $Users->deleteUser($row['id']);
        }

        // Delete all non-essential groups
        $this->writeInfo('cleanup.delete_groups');

        $rootGroupId = (int)QUI::conf('globals', 'root');

        $results = QUI::getDataBase()->fetch([
            'select' => ['id'],
            'from'   => QUI::getDBTableName('groups'),
            'where'  => [
                'id' => [
                    'type'  => 'NOT IN',
                    'value' => [0, 1, $rootGroupId]
                ]
            ]
        ]);

        $Groups = QUI::getGroups();

        foreach ($results as $row) {
            $Groups->get($row['id'])->delete();
        }

        // Delete all projects (but leave one for now)
        $this->writeInfo('cleanup.delete_projects');

        $Projects        = QUI::getProjectManager();
        $StandardProject = $Projects->getStandard();

        // Delete media files of standard project first
        $dir = $StandardProject->getMedia()->getPath();

        if (is_dir($dir)) {
            $it    = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }

            rmdir($dir);
        }

        $StandardProject->rename('old_standard_project');

        /** @var QUI\Projects\Project $Project */
        foreach ($Projects->getProjects(true) as $Project) {
            if ($Project->getName() === $StandardProject->getName()) {
                // standard project is deleted at a later time
                // because QUIQQER needs at least 1 project at any time
                continue;
            }

            $Projects->deleteProject($Project);
        }

        // Delete all crons
        $this->writeInfo('cleanup.delete_crons');

        $result = QUI::getDataBase()->fetch([
            'select' => ['id'],
            'from'   => CronManager::table()
        ]);

        $cronIds = [];

        foreach ($result as $row) {
            $cronIds[] = $row['id'];
        }

        $CronManager = new CronManager();
        $CronManager->deleteCronIds($cronIds);

        // Delete all import permissions
        $this->writeInfo('cleanup.delete_permissions');

        $PermissionManager = QUI::getPermissionManager();

        QUI::getDataBase()->delete(
            $PermissionManager::table(),
            [
                'src' => 'cms-import'
            ]
        );

        // Delete all import translations
        QUI::getDataBase()->delete(
            QUI\Translator::table(),
            [
                'groups' => [
                    'type'  => 'LIKE%',
                    'value' => 'cms-import/'
                ]
            ]
        );
    }

    /**
     * Write info to Console tool
     *
     * @param string $msg
     * @param array $localeVars (optional) - Variables for msg locale
     * @return void
     */
    protected function writeInfo($msg, $localeVars = [])
    {
        $msg = QUI::getLocale()->get('quiqqer/cms-import', 'import.msg.'.$msg, $localeVars);
        $this->ConsoleTool->writeInfo($msg);
    }

    /**
     * Write warning to Console tool
     *
     * @param string $msg
     * @param array $localeVars (optional) - Variables for msg locale
     * @param QUI\CmsImport\Entities\AbstractImportEntity $ImportEntitiy - The concerned import entity
     * @return void
     */
    protected function writeWarning($msg, $localeVars = [], $ImportEntitiy = null)
    {
        $msg = QUI::getLocale()->get('quiqqer/cms-import', 'import.warning.'.$msg, $localeVars);
        $this->ConsoleTool->writeWarning($msg);

        if (empty($ImportEntitiy)) {
            $this->addReviewFlag($msg);
        } else {
            $ImportEntitiy->addReviewFlag($msg);
            $this->reviewEntities[] = $ImportEntitiy;
        }
    }

    /**
     * Write Exception message to stdout
     *
     * @param \Exception $Exception
     * @return void
     */
    protected function writeException(\Exception $Exception)
    {
        $this->ConsoleTool->writeError($Exception->getMessage());
    }

    /**
     * Write error to Console tool
     *
     * @param string $msg
     * @param array $localeVars (optional) - Variables for msg locale
     * @param QUI\CmsImport\Entities\AbstractImportEntity $ImportEntitiy - The concerned import entity
     * @return void
     */
    protected function writeError($msg, $localeVars = [], $ImportEntitiy = null)
    {
        $msg = QUI::getLocale()->get('quiqqer/cms-import', 'import.error.'.$msg, $localeVars);
        $this->ConsoleTool->writeError($msg);

        if (empty($ImportEntitiy)) {
            $this->addReviewFlag($msg);
        } else {
            $ImportEntitiy->addReviewFlag($msg);
            $this->reviewEntities[] = $ImportEntitiy;
        }
    }

    /**
     * Write header to Console tool
     *
     * @param string $msg
     * @param array $localeVars (optional) - Variables for msg locale
     * @return void
     */
    protected function writeHeader($msg, $localeVars = [])
    {
        $msg = QUI::getLocale()->get('quiqqer/cms-import', 'import.header.'.$msg, $localeVars);
        $this->ConsoleTool->writeHeader($msg);
    }

    /**
     * Add a review flag to the import process
     *
     * @param string $msg
     * @param string $section
     * @return void
     */
    public function addReviewFlag($msg, $section = self::IMPORT_SECTION_GENERAL)
    {
        if (!isset($this->reviewFlags[$section])) {
            $this->reviewFlags[$section] = [];
        }

        $this->reviewFlags[$section][] = $msg;
    }

    /**
     * Write review log
     *
     * @return void
     */
    protected function writeReviewLog()
    {
        foreach ($this->reviewEntities as $ImportEntity) {
            $section = $ImportEntity->getImportSection();

            if (!isset($this->reviewFlags[$section])) {
                $this->reviewFlags[$section] = [];
            }

            $prefix = '#'.$ImportEntity->getIdentifier();

            if ($ImportEntity->getAttribute(self::ENTITY_ATTRIBUTE_QUIQQER_ID)) {
                $prefix .= ' (QUIQQER ID: #'.$ImportEntity->getAttribute(self::ENTITY_ATTRIBUTE_QUIQQER_ID).')';
            }

            foreach ($ImportEntity->getReviewFlags() as $reviewMsg) {
                $this->reviewFlags[$section][] = $prefix.' - '.$reviewMsg;
            }
        }

        $reviewLines = [];
        $L           = QUI::getLocale();
        $lg          = 'quiqqer/cms-import';
        $sections    = [];

        foreach ($this->reviewFlags as $section => $reviewMessages) {
            if (!isset($sections[$section])) {
                $reviewLines[] = "\n".$L->get($lg, 'review.section.header.'.$section);
                $reviewLines[] = "==================================================\n";

                $sections[$section] = true;
            }

            foreach ($reviewMessages as $reviewMsg) {
                $reviewLines[] = $reviewMsg;
            }
        }

        if (empty($reviewLines)) {
            return;
        }

        $reviewFile = $this->varDir.'review.log';

        file_put_contents($reviewFile, implode("\n", $reviewLines));

        $this->writeHeader('review');

        $this->writeInfo('review_log', [
            'file' => $reviewFile
        ]);
    }
}
