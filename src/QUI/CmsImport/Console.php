<?php

namespace QUI\CmsImport;

use QUI;

/**
 * Console tool for PackageStore update
 *
 * @author www.pcsg.de (Patrick Müller)
 */
class Console extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:cms-import')
            ->setDescription(
                'Execute a QUIQQER CMS import'
            );
    }

    /**
     * Execute the console tool
     */
    public function execute()
    {
        if (!QUI\Permissions\Permission::isSU()) {
            $this->exitFail("The QUIQQER CMS Import can only be executed by a SuperUser!");
        }

        if (!$this->isSystemEmpty()) {
            $this->writeWarning(
                'It seems your QUIQQER system is not clean as it contains at least'
                .' one additional user, group or project. It is advised that you import'
                .' into a clean QUIQQER system.'
            );
        }

        $userInput = $this->writePrompt(
            'Do you want do CLEANUP your QUIQQER system before executing the import?'
            . ' (This will delete all non-standard users, groups and all projects that'
            .' are not in the import data) (Y/n)'
        );

        if (mb_strtolower($userInput) === 'n') {
            $cleanup = false;
        } else {
            $cleanup = true;
        }



        $this->exitSuccess();
    }

    /**
     * Check if the current QUIQQER system is empty (fresh setup)
     *
     * @return bool
     */
    protected function isSystemEmpty()
    {
        // Check users
        $result = QUI::getDataBase()->fetch([
            'count' => 1,
            'from'  => QUI::getDBTableName('users')
        ]);

        $userCount = (int)current(current($result));

        if ($userCount > 1) {
            return false;
        }

        // Check groups
        $result = QUI::getDataBase()->fetch([
            'count' => 1,
            'from'  => QUI::getDBTableName('groups')
        ]);

        $groupCount = (int)current(current($result));

        if ($groupCount > 3) {
            return false;
        }

        return true;
    }

    /**
     * Output and return a user prompt
     *
     * @param string $msg
     * @return string
     */
    protected function writePrompt($msg)
    {
        $this->writeLn("[Q] - ".$msg.": ", 'white');
        return $this->readInput();
    }

    /**
     * Write an info msg
     *
     * @param string $msg
     * @return void
     */
    protected function writeInfo($msg)
    {
        $this->writeLn("[INFO] - ".$msg, 'cyan');
    }

    /**
     * Write a warning msg
     *
     * @param string $msg
     * @return void
     */
    protected function writeWarning($msg)
    {
        $this->writeLn("[WARNING] - ".$msg, 'yellow');
    }

    /**
     * Write an error msg
     *
     * @param string $msg
     * @return void
     */
    protected function writeError($msg)
    {
        $this->writeLn("[ERROR] - ".$msg, 'red');
    }

    /**
     * Exits the console tool with a success msg and status 0
     *
     * @return void
     */
    protected function exitSuccess()
    {
        $this->writeLn("\n[SCRIPT SUCCESSFULLY EXECUTED! IMPORT IS COMPLETE.]\n\n", 'green');

        exit(0);
    }

    /**
     * Exits the console tool with an error msg and status 1
     *
     * @param $msg
     * @return void
     */
    protected function exitFail($msg)
    {
        $this->writeLn("\n");
        $this->writeError($msg);
        $this->writeLn("\n[SCRIPT ABORTED! IMPORT WAS NOT COMPLETED.]\n\n", 'red');

        exit(1);
    }
}
