<?php

namespace QUI\CmsImport;

use QUI;
use QUI\Tags\Manager as TagManager;
use QUI\Tags\Groups\Handler as TagGroupManager;
use QUI\CmsImport\Entities\ImportSite;
use QUI\CmsImport\Entities\ImportProject;
use QUI\Cron\Manager as CronManager;
use QUI\CmsImport\Hierarchy\SiteHierarchy;
use QUI\CmsImport\Hierarchy\SiteItem;
use QUI\CmsImport\Hierarchy\ChildrenIteratorInterface;
use QUI\Permissions\Manager as PermissionManager;
use QUI\CmsImport\ItemList\UserList;

/**
 * Class Import
 *
 * Imports data and structure from a Import provider to the current QUIQQER system
 */
class Import extends QUI\QDOM
{
    const IMPORT_SECTION_GENERAL = 'general';
    const IMPORT_SECTION_SITES   = 'sites';
    const IMPORT_SECTION_MEDIA   = 'media';
    const IMPORT_SECTION_USERS   = 'users';
    const IMPROT_SECTION_GROUPS  = 'groups';

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
     */
    public function __construct(ImportProviderInterface $ImportProvider, $settings = [])
    {
        $this->setAttributes([
            'cleanup'            => true,
            'importTags'         => false,
            'importSites'        => false,
            'importMedia'        => false,
            'importUsers'        => false,
            'importGroups'       => false,
            'importSystemConfig' => true
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
        $this->importProjects();

        // Delete old standard project
        if ($this->getAttribute('cleanup')) {
            $this->writeHeader('delete_old_standard_project');

            $Projects   = QUI::getProjectManager();
            $OldProject = $Projects->getProject('old_standard_project');
            $Projects->deleteProject($OldProject);
        }

        // Tags / tag groups
        if ($this->getAttribute('importTags')) {
            if (QUI::getPackageManager()->isInstalled('quiqqer/tags')) {
                $this->importTags();
                $this->importTagGroups();
            } else {
                $this->writeWarning('tags_package_not_installed');
            }
        }

        // Groups
        if ($this->getAttribute('importGroups')) {
            $this->importGroups();
        }

        // Users
        if ($this->getAttribute('importUsers')) {
            $this->importUsers();
        }

        // Sites
        if ($this->getAttribute('importSites')) {
            $this->importSites();
        }

        // Media
        if ($this->getAttribute('importMedia')) {
            $this->importMedia();
        }

        // System config
        if ($this->getAttribute('importSystemConfig')) {
            $this->importSystemConfig();
        }
    }

    /**
     * Start project import
     *
     * @return void
     */
    protected function importProjects()
    {
        $projects = $this->ImportProvider->getProjects();
        $Projects = QUI::getProjectManager();

        $this->importData['projects'] = [];

        foreach ($projects as $ImportProject) {
            $this->writeHeader('project', ['project' => $ImportProject->getName()]);

            try {
                // this is a badfix! QUIQQER caches the content of the main conf file
                // and at this point may have old config data; this forces QUIQQER to reload
                // the config from the filesystem.
                QUI::$Configs = [];

                $NewProject = $Projects->createProject(
                    $ImportProject->getName(),
                    $ImportProject->getDefaultLang(),
                    $ImportProject->getLangs()
                );

                $Projects->setConfigForProject($NewProject->getName(), $ImportProject->getAttributes());
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                $this->writeError($Exception->getMessage());

                continue;
            }

            // Cache imported project
            $this->importData['projects'][$ImportProject->getName()] = $NewProject;

            $this->writeInfo('project.success', ['project' => $NewProject->getName()]);
        }
    }

    /**
     * Import tag groups
     *
     * @return void
     */
    protected function importTagGroups()
    {
        /**
         * @var string $projectIdentifier
         * @var QUI\Projects\Project $QuiqqerProject
         */
        foreach ($this->importData['projects'] as $projectIdentifier => $QuiqqerProject) {
            foreach ($QuiqqerProject->getLanguages() as $lang) {
                $this->writeHeader('project_tag_groups', [
                    'projectIdentifier' => $projectIdentifier,
                    'lang'              => $lang
                ]);

                $TagProject  = QUI::getProject($QuiqqerProject->getName(), $lang);
                $TagManager  = new TagManager($TagProject);
                $tagGroups   = $this->ImportProvider->getTagGroups($projectIdentifier, $lang);
                $tagGroupIds = [];

                // import tag groups
                foreach ($tagGroups as $tagGroup => $data) {
                    if (empty($tagGroup)) {
                        continue;
                    }

                    $this->writeInfo('project_tag_group', [
                        'tagGroupTitle' => $tagGroup
                    ]);

                    $TagGroup = TagGroupManager::create($TagProject, $tagGroup);

                    $TagGroup->setGenerator('quiqqer/cms-import');
                    $TagGroup->setGenerateStatus(true);

                    if (!empty($data['description'])) {
                        $TagGroup->setDescription($data['description']);
                    }

                    // Add tags to tag group
                    foreach ($data['tags'] as $tagTitle) {
                        $tag = $TagManager->getByTitle($tagTitle);
                        $TagGroup->addTag($tag['tag']);
                    }

                    $TagGroup->save();
                    $tagGroupIds[$tagGroup] = $TagGroup->getId();
                }

                // set parent tag groups
                foreach ($tagGroups as $tagGroup => $data) {
                    if (empty($data['parentGroup'])) {
                        continue;
                    }

                    $TagGroup = TagGroupManager::get($TagProject, $tagGroupIds[$tagGroup]);
                    $TagGroup->setParentGroup($tagGroupIds[$data['parentGroup']]);
                    $TagGroup->save();
                }
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
        /**
         * @var string $projectIdentifier
         * @var QUI\Projects\Project $QuiqqerProject
         */
        foreach ($this->importData['projects'] as $projectIdentifier => $QuiqqerProject) {
            foreach ($QuiqqerProject->getLanguages() as $lang) {
                $this->writeHeader('project_tags', [
                    'projectIdentifier' => $projectIdentifier,
                    'lang'              => $lang
                ]);

                $TagProject = QUI::getProject($QuiqqerProject->getName(), $lang);
                $TagManager = new TagManager($TagProject);
                $tags       = $this->ImportProvider->getTags($projectIdentifier, $lang);

                foreach ($tags as $tagTitle => $data) {
                    if (empty($tagTitle)) {
                        continue;
                    }

                    $this->writeInfo('project_tag', [
                        'tagTitle' => $tagTitle
                    ]);

                    $tagAttributes = [];

                    if (!empty($data['description'])) {
                        $tagAttributes['desc'] = $data['description'];
                    }

                    $TagManager->add($tagTitle, $tagAttributes);
                }
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
                            ]);

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
     * @throws QUI\Exception
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
     * @throws QUI\Exception
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

        $config      = $this->ImportProvider->getSystemConfig();
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
    }

    /**
     * Create sites in the QUIQQER system
     *
     * @param QUI\Projects\Project $QuiqqerProject
     * @param string|int $projectIdentifier - Unique ImportProject identifier
     * @param ChildrenIteratorInterface $SiteTree
     * @param QUIQQERImportSite|null $RootQuiqqerSite
     * @param array &$importedSiteIds
     * @return array
     * @throws QUI\Exception
     */
    protected function createSites(
        QUI\Projects\Project $QuiqqerProject,
        $projectIdentifier,
        ChildrenIteratorInterface $SiteTree,
        QUIQQERImportSite $RootQuiqqerSite = null,
        &$importedSiteIds = []
    ) {
        $lang     = $QuiqqerProject->getLang();
        $sitesTbl = QUI::getDBProjectTableName('sites', $QuiqqerProject);

        /** @var SiteItem $ChildSiteItem */
        foreach ($SiteTree->walkChildren() as $ChildSiteItem) {
            // Site links are not created as actual sites but as links (created after all sites are created)
            if ($ChildSiteItem->isLink()) {
                continue;
            }

            $siteIdentifier       = $ChildSiteItem->getId();
            $ImportSite           = $this->ImportProvider->getSite($siteIdentifier, $projectIdentifier, $lang);
            $importQuiqqerSiteId  = $ImportSite->getQuiqqerSiteId();
            $importSiteAttributes = $ImportSite->getAttributes();

            $this->writeInfo('site_start', [
                'siteIdentifier' => $siteIdentifier,
                'siteTitle'      => $ImportSite->getAttribute('title')
            ]);

            // Check if site ID has already been imported
            if (!empty($importQuiqqerSiteId) && isset($importedSiteIds[$importQuiqqerSiteId])) {
                $this->writeWarning(
                    'site_duplicate_id',
                    [
                        'siteIdentifier' => $importQuiqqerSiteId,
                        'siteTitle'      => $ImportSite->getAttribute('title')
                    ]
                );

                continue;
            }

            // Special case: QUIQQER root site
            if (empty($RootQuiqqerSite)) {
                $RootQuiqqerSite = new QUIQQERImportSite($QuiqqerProject, 1);
                $RootQuiqqerSite->setAttributes($importSiteAttributes);
                $RootQuiqqerSite->save();

                $NewSite = $RootQuiqqerSite;
            } else {
                $createAttributes = $importSiteAttributes;

                if (!empty($importQuiqqerSiteId)) {
                    $createAttributes['id'] = $importQuiqqerSiteId;
                }

                $newSiteId = $RootQuiqqerSite->createChild($createAttributes);

                // Set all attributes to the site
                $NewSite = new QUIQQERImportSite($QuiqqerProject, $newSiteId);
                $NewSite->setAttributes($importSiteAttributes);
                $NewSite->save();
            }

            $newSiteId = $NewSite->getId();

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
                $NewSite->activate();
            }

            // Import QUIQQER tags
            $tags = $ImportSite->getTags();

            if ($this->quiqqerPackages['quiqqer/tags']) {
                $this->importSiteTags($ImportSite, $NewSite);
            } elseif (!empty($tags)) {
                $this->writeWarning('site_tags_plugin_missing');
            }

            $NewSite->save();

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
     * @param ChildrenIteratorInterface $SiteTree
     * @param array $importSiteMap - Map of imported sites (quiqqer site id => siteIdentifier)
     * @return void
     * @throws QUI\Exception
     */
    protected function createSiteLinks(
        QUI\Projects\Project $QuiqqerProject,
        ChildrenIteratorInterface $SiteTree,
        $importSiteMap
    ) {
        /** @var SiteItem $ChildSiteItem */
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
     * @throws QUI\Exception
     */
    protected function importSiteTags(ImportSite $ImportSite, QUI\Projects\Site\Edit $QuiqqerSite)
    {
        $QuiqqerProject = $QuiqqerSite->getProject();
        $TagManager     = new TagManager($QuiqqerProject);

        // Tags
        $this->writeInfo('site_tags_start');

        foreach ($ImportSite->getTags() as $tagTitle) {
            if (!$TagManager->existsTagTitle($tagTitle)) {
                $this->writeWarning('site_tags_tag_not_found', [
                    'tagTitle' => $tagTitle
                ]);

                continue;
            }

            $tag = $TagManager->getByTitle($tagTitle);
            $TagManager->addTagToSite($QuiqqerSite->getId(), $tag['tag']);
        }

        // Tag Groups
        $this->writeInfo('site_tag_groups_start');

        foreach ($ImportSite->getTagGroups() as $tagGroupTitle) {
            $groups = TagGroupManager::getGroups($QuiqqerProject, [
                'where' => [
                    'title' => $tagGroupTitle
                ]
            ]);

            if (empty($groups)) {
                $this->writeWarning('site_tags_tag_group_not_found', [
                    'tagGroupTitle' => $tagGroupTitle
                ]);

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
        $QuiqqerSite->load('quiqqer/tags');
    }

    /**
     * Create media items/structure in the QUIQQER system
     *
     * @param QUI\Projects\Project $QuiqqerProject
     * @param string|int $projectIdentifier - Unique ImportProject identifier
     * @param ChildrenIteratorInterface $MediaTree
     * @param QUIQQERImportMediaFolder $RootQuiqqerMediaFolder
     * @param array &$importedMediaIds
     * @return array
     * @throws QUI\Exception
     */
    protected function createMedia(
        QUI\Projects\Project $QuiqqerProject,
        $projectIdentifier,
        ChildrenIteratorInterface $MediaTree,
        QUIQQERImportMediaFolder $RootQuiqqerMediaFolder = null,
        &$importedMediaIds = []
    ) {
        if (empty($RootQuiqqerMediaFolder)) {
            $RootQuiqqerMediaFolder = $this->getQuiqqerImportMediaFolder(1, $QuiqqerProject);
        }

        /** @var QUI\CmsImport\Hierarchy\MediaItem $ChildMediaItem */
        foreach ($MediaTree->walkChildren() as $ChildMediaItem) {
            $mediaItemIdentifier   = $ChildMediaItem->getId();
            $ImportMediaItem       = $this->ImportProvider->getMediaItem($mediaItemIdentifier, $projectIdentifier);
            $importQuiqqerMediaId  = $ImportMediaItem->getQuiqqerMediaId();
            $importMediaAttributes = $ImportMediaItem->getAttributes();

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
                    ]
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
                    $NewItem = $RootQuiqqerMediaFolder->createFolder(
                        $ImportMediaItem->getTitle(),
                        $importQuiqqerMediaId ?: null
                    );

                    $NewItem->setAttributes($importMediaAttributes);
                    $NewItem->save();

                    // Activate media item
                    if ($importMediaAttributes['active']) {
                        $NewItem->activate();
                    }

                    $newRootId = $NewItem->getId();
                } else {
                    $mediaFile = $ImportMediaItem->getFile();

                    // Check if file was given and/or exists first
                    if (empty($mediaFile)) {
                        $this->writeWarning('media_file_not_set', [
                            'identifier' => $mediaItemIdentifier
                        ]);
                    } elseif (!file_exists($mediaFile)) {
                        $this->writeWarning('media_file_not_found', [
                            'identifier' => $mediaItemIdentifier,
                            'file'       => $mediaFile
                        ]);
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
                            ]);
                        }

                        $NewItem = $RootQuiqqerMediaFolder->uploadFile(
                            $mediaFile,
                            QUIQQERImportMediaFolder::FILE_OVERWRITE_NONE,
                            $importQuiqqerMediaId ?: null
                        );

                        if ($deleteFileAfterUpload) {
                            unlink($mediaFile);
                        }

                        $importMediaAttributes['title'] = $ImportMediaItem->getTitle();

                        $NewItem->setAttributes($importMediaAttributes);
                        $NewItem->save();

                        // Activate media item
                        if ($importMediaAttributes['active']) {
                            $NewItem->activate();
                        }

                        $newRootId = $NewItem->getId();
                    }
                }

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
                    ]);
                }
            }
        }

        return $importedMediaIds;
    }

    /**
     * Create group hierarchy in the QUIQQER system
     *
     * @param ChildrenIteratorInterface $GroupTree
     * @param QUIQQERImportGroup $RootQuiqqerGroup
     * @return void
     * @throws QUI\Exception
     */
    protected function createGroups(
        ChildrenIteratorInterface $GroupTree,
        QUIQQERImportGroup $RootQuiqqerGroup = null
    ) {
        $quiqqerRootGroupId = QUI::conf('globals', 'root');
        $Permission         = new PermissionManager();

        if (empty($RootQuiqqerGroup)) {
            $RootQuiqqerGroup = new QUIQQERImportGroup($quiqqerRootGroupId);
        }

        /** @var QUI\CmsImport\Hierarchy\GroupItem $ChildGroupItem */
        foreach ($GroupTree->walkChildren() as $ChildGroupItem) {
            $groupIdentifier       = $ChildGroupItem->getId();
            $ImportGroup           = $this->ImportProvider->getGroup($groupIdentifier);
            $importQuiqqerGroupId  = $ImportGroup->getQuiqqerGroupId();
            $importGroupAttributes = array_merge(
                $ImportGroup->getAttributes(),
                [
                    'name' => $ImportGroup->getName()
                ]
            );

            $this->writeInfo('group_start', [
                'identifier' => $groupIdentifier
            ]);

            // Check if group ID has already been imported
            if (!empty($importQuiqqerGroupId)
                && in_array($importQuiqqerGroupId, $this->importData['groups'])
            ) {
                $this->writeWarning(
                    'group_duplicate_id',
                    [
                        'identifier' => $groupIdentifier,
                        'id'         => $importQuiqqerGroupId
                    ]
                );

                continue;
            }

            if ($importQuiqqerGroupId == 1 && $RootQuiqqerGroup->getId() == $quiqqerRootGroupId) {
                $NewGroup = $RootQuiqqerGroup;
            } else {
                $NewGroup = $RootQuiqqerGroup->createChild(
                    $ImportGroup->getName(),
                    null,
                    $importQuiqqerGroupId ?: null
                );
            }

            $NewGroup->setAttributes($importGroupAttributes);
            $NewGroup->save();

            // Activate group
            if (!empty($importGroupAttributes['active'])) {
                $NewGroup->activate();
            }

            // Set admin access permission
            if ($ImportGroup->hasAdminAccess()) {
                $groupPermissions                  = $Permission->getPermissions($NewGroup);
                $groupPermissions['quiqqer.admin'] = true;
                $Permission->setPermissions($NewGroup, $groupPermissions);
            }

            $this->importData['groups'][$groupIdentifier] = $NewGroup->getId();

            $this->writeInfo('group_finish', [
                'identifier'       => $groupIdentifier,
                'quiqqerGroupId'   => $NewGroup->getId(),
                'quiqqerGroupName' => $NewGroup->getName()
            ]);

            if ($ChildGroupItem->hasChildren()) {
                $this->createGroups($ChildGroupItem, new QUIQQERImportGroup($NewGroup->getId()));
            }
        }
    }

    /**
     * Create QUIQQER users from a UserList
     *
     * @param UserList $UserList
     * @return void
     */
    protected function createUsers(UserList $UserList)
    {
        $UserManager = new QUIQQERImportUserManager();
        $DB          = QUI::getDataBase();
        $usersTable  = QUI\Users\Manager::table();

        /** @var QUI\CmsImport\ItemList\UserItem $UserItem */
        foreach ($UserList->walkChildren() as $UserItem) {
            $ImportUser = $this->ImportProvider->getUser($UserItem->getId());

            $this->writeInfo('user_start', [
                'identifier' => $ImportUser->getIdentifier(),
                'username'   => $ImportUser->getUsername()
            ]);

            try {
                $NewUser = $UserManager->createChild(
                    $ImportUser->getUsername(),
                    null,
                    $ImportUser->getQuiqqerUserId()
                );
            } catch (\Exception $Exception) {
                $this->writeException($Exception);
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
            }

            $NewUser->save();
            $NewUser->refresh();

            // Activate
            if ($importUserAttributes['active']) {
                try {
                    $NewUser->activate();
                } catch (\Exception $Exception) {
                    $this->writeException($Exception);
                }
            }

            $this->writeInfo('user_finish', [
                'quiqqerUserId'   => $NewUser->getId(),
                'quiqqerUsername' => $NewUser->getUsername()
            ]);
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
     * @return void
     */
    protected function writeWarning($msg, $localeVars = [])
    {
        $msg = QUI::getLocale()->get('quiqqer/cms-import', 'import.warning.'.$msg, $localeVars);
        $this->ConsoleTool->writeWarning($msg);
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
     * @return void
     */
    protected function writeError($msg)
    {
        $this->ConsoleTool->writeError($msg);
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
        $Now = new \DateTime();

        if (!isset($this->reviewFlags[$section])) {
            $this->reviewFlags[$section] = [];
        }

        $this->reviewFlags[$section][$Now->format('Y-m-d H:i:s')] = $msg;
    }
}
