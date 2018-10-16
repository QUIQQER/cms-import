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

/**
 * Class Import
 *
 * Imports data and structure from a Import provider to the current QUIQQER system
 */
class Import extends QUI\QDOM
{
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
     * Import constructor.
     *
     * @param ImportProviderInterface $ImportProvider
     * @param array $settings
     */
    public function __construct(ImportProviderInterface $ImportProvider, $settings = [])
    {
        $this->setAttributes([
            'cleanup' => true
        ]);

        $this->setAttributes($settings);
        $this->ImportProvider = $ImportProvider;

        $PackageManager = QUI::getPackageManager();

        foreach ($this->quiqqerPackages as $package => $isInstalled) {
            $this->quiqqerPackages[$package] = $PackageManager->isInstalled($package);
        }
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
        if (QUI::getPackageManager()->isInstalled('quiqqer/tags')) {
            $this->importTags();
            $this->importTagGroups();
        } else {
            $this->writeWarning('tags_package_not_installed');
        }

        // Sites
        $this->importSites();
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
        $Projects = QUI::getProjectManager();
        $projects = $Projects->getProjects(true);

        /** @var QUI\Projects\Project $Project */
        foreach ($projects as $Project) {
            $langs         = $Project->getLanguages();
            $project       = $Project->getName();
            $importedSites = [];

            // Create sites
            foreach ($langs as $lang) {
                $TargetProject        = $Projects->getProject($project, $lang);
                $SiteHierarchy        = $this->ImportProvider->getSiteHierarchy($project, $lang);
                $importedSites[$lang] = $this->createSites($TargetProject, $SiteHierarchy);
                $this->createSiteLinks($TargetProject, $SiteHierarchy, $importedSites[$lang]);
            }

            // Create language links for $lang
            foreach ($importedSites as $lang => $importSites) {
                $SourceProject = $Projects->getProject($project, $lang);

                foreach ($importSites as $quiqqerSiteId => $siteIdentifier) {
                    $ImportSite  = $this->ImportProvider->getSite($siteIdentifier, $project, $lang);
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

                        $TargetProject     = $Projects->getProject($project, $targetLang);
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
     * @param QUI\Projects\Project $QuiqqerProject
     * @param ChildrenIteratorInterface $ChildrenIterator
     * @param QUIQQERImportSite|null $RootQuiqqerSite
     * @param array &$importedSiteIds
     * @return array
     * @throws QUI\Exception
     */
    protected function createSites(
        QUI\Projects\Project $QuiqqerProject,
        ChildrenIteratorInterface $ChildrenIterator,
        QUIQQERImportSite $RootQuiqqerSite = null,
        &$importedSiteIds = []
    ) {
        $project = $QuiqqerProject->getName();
        $lang    = $QuiqqerProject->getLang();

        /** @var SiteItem $ChildSiteItem */
        foreach ($ChildrenIterator->walkChildren() as $ChildSiteItem) {
            // Site links are not created as actual sites but as links (created after all sites are created)
            if ($ChildSiteItem->isLink()) {
                continue;
            }

            $siteIdentifier       = $ChildSiteItem->getId();
            $ImportSite           = $this->ImportProvider->getSite($siteIdentifier, $project, $lang);
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

            // Activate site
            if ($importSiteAttributes['active']) {
                $NewSite->activate();
            }

            $newSiteId = $NewSite->getId();

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
                $this->createSites($QuiqqerProject, $ChildSiteItem, $NewSite, $importedSiteIds);
            }
        }

        return $importedSiteIds;
    }

    /**
     * @param QUI\Projects\Project $QuiqqerProject
     * @param ChildrenIteratorInterface $ChildrenIterator
     * @param array $importSiteMap - Map of imported sites (quiqqer site id => siteIdentifier)
     * @return void
     * @throws QUI\Exception
     */
    protected function createSiteLinks(
        QUI\Projects\Project $QuiqqerProject,
        ChildrenIteratorInterface $ChildrenIterator,
        $importSiteMap
    ) {
        /** @var SiteItem $ChildSiteItem */
        foreach ($ChildrenIterator->walkChildren() as $ChildSiteItem) {
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
        if (is_null($this->ConsoleTool)) {
            return;
        }

        $msg = QUI::getLocale()->get('quiqqer/cms-import', 'import.msg.'.$msg, $localeVars);

        if (!is_null($this->ConsoleTool)) {
            $this->ConsoleTool->writeInfo($msg);
        }
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
        if (is_null($this->ConsoleTool)) {
            return;
        }

        $msg = QUI::getLocale()->get('quiqqer/cms-import', 'import.warning.'.$msg, $localeVars);

        if (!is_null($this->ConsoleTool)) {
            $this->ConsoleTool->writeWarning($msg);
        }
    }

    /**
     * Write error to Console tool
     *
     * @param string $msg
     * @return void
     */
    protected function writeError($msg)
    {
        if (!is_null($this->ConsoleTool)) {
            $this->ConsoleTool->writeError($msg);
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
        if (is_null($this->ConsoleTool)) {
            return;
        }

        $msg = QUI::getLocale()->get('quiqqer/cms-import', 'import.header.'.$msg, $localeVars);

        if (!is_null($this->ConsoleTool)) {
            $this->ConsoleTool->writeHeader($msg);
        }
    }
}
