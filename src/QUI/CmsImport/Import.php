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

        // Projects


        // Sites
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
        $this->ConsoleTool->writeHeader("Cleaning up QUIQQER system...");

        // Delete all user except for the root user
        $this->write("Deleting all users (except non-root)");

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
        $this->write("Deleting all non-essential groups...");

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
        $this->write("Deleting all projcets (except for the standard project)...");

        $Projects        = QUI::getProjectManager();
        $StandardProject = $Projects->getStandard();

        /** @var QUI\Projects\Project $Project */
        foreach ($Projects->getProjects(true) as $Project) {
            if ($Project->getName() === $StandardProject->getName()) {
                continue;
            }

            $Projects->deleteProject($Project);
        }
    }

    /**
     * Write info to Console tool
     *
     * @param string $msg
     * @return void
     */
    protected function write($msg)
    {
        if (!is_null($this->ConsoleTool)) {
            $this->ConsoleTool->writeInfo($msg);
        }
    }
}
