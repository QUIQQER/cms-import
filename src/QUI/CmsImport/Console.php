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
//        if (!QUI\Permissions\Permission::isSU()) {
            $this->exitFail("The QUIQQER CMS Import can only be executed by a SuperUser!");
//        }

        $settings = [
            'package'           => $this->getArgument('package'),
            'updateAllVersions' => $this->getArgument('updateAllVersions')
        ];

        $this->writeLn("\nStarte Update...\n\n");

        \QUI\PackageStore\Manager::startUpdate($settings);
        $this->exitSuccess();
    }

    protected function writeInfo($msg)
    {
        $this->writeLn("[INFO] - ".$msg, 'cyan');
    }

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
        $this->writeLn('Update erfolgreich ausgeführt');
        $this->writeLn("");

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
        $this->writeError('Skript-Abbruch wegen Fehler:');
        $this->writeLn("");
        $this->writeError($msg);
        $this->writeLn("");
        $this->writeLn("");

        exit(1);
    }
}
