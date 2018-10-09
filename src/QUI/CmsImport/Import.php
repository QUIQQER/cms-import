<?php

namespace QUI\CmsImport;

use QUI;


/**
 * Class Import
 *
 * Imports data and structure from a Import provider to the current QUIQQER system
 */
class Import extends QUI\QDOM
{
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

        // Sites
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

        foreach ($projects as $ImportProject) {
            $this->writeHeader('project', ['project' => $ImportProject->getName()]);

            try {
                // this is a badfix! QUIQQER caches the content of the main conf file
                // and at this point may have old config data
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

            $this->writeInfo('project.success', ['project' => $NewProject->getName()]);
        }
    }

    protected function importSites()
    {
        $Projects = QUI::getProjectManager();
        $projects = $Projects->getProjects(true);

        /** @var QUI\Projects\Project $Project */
        foreach ($projects as $Project) {
            $langs   = $Project->getLanguages();
            $project = $Project->getName();

            foreach ($langs as $lang) {
                $TargetProject = $Projects->getProject($project, $lang);
                $importSites   = $this->ImportProvider->getSites($project, $lang);

                foreach ($importSites as $ImportSite) {
                    
                }
            }
        }

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
