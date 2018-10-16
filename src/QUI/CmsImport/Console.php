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
        QUI::getLocale()->setCurrent('de'); // @todo keep variable

        $rootUserId = (int)QUI::conf('globals', 'rootuser');

        if (QUI::getUserBySession()->getId() !== $rootUserId) {
            $this->exitFail("The QUIQQER CMS Import can only be executed by the root user!");
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
            .' (This will delete all users (except for the root user), groups and all projects that'
            .' are not in the import data) (Y/n)'
        );

        if (mb_strtolower($userInput) === 'n') {
            $cleanup = false;
        } else {
            $cleanup = true;
        }

        // Choose ImportProvider
        do {
            $this->writeInfo("Please choose the ImportProvider you want to execute:\n");

            $providers = $this->getImportProviders();

            foreach ($providers as $k => $ImportProvider) {
                $this->writeLn("[".($k + 1)."] ".$ImportProvider->getTitle()." - ".$ImportProvider->getDescription());
            }

            $key = $this->writePrompt("Which ImportProvider shall be executed?");

            if (empty($key)) {
                continue;
            }

            $key = $key - 1;

            if (empty($providers[$key])) {
                continue;
            }

            $SelectedProvider = $providers[$key];
            break;
        } while (true);

        $Import = new Import($SelectedProvider, [
            'cleanup' => $cleanup
        ]);

        $Import->setConsoleTool($this);

        try {
            $Import->start();
        } catch (\Exception $Exception) {
            \QUI\System\Log::writeDebugException($Exception);
            $this->exitFail($Exception->getMessage());
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
     * @param string $defaultValue (optional)
     * @return string
     */
    public function writePrompt($msg, $defaultValue = null)
    {
        if (empty($defaultValue)) {
            $this->writeLn("[Q] - ".$msg.": ", 'white');
        } else {
            $this->writeLn("[Q] - ".$msg." [".$defaultValue."]: ", 'white');
        }

        $input = $this->readInput();

        if (empty($defaultValue)) {
            return $input;
        }

        return empty($input) ? $defaultValue : $input;
    }

    /**
     * Write an info msg
     *
     * @param string $msg
     * @return void
     */
    public function writeInfo($msg)
    {
        $this->writeLn("[INFO] - ".$msg, 'cyan');
    }

    /**
     * Write a warning msg
     *
     * @param string $msg
     * @return void
     */
    public function writeWarning($msg)
    {
        $this->writeLn("[WARNING] - ".$msg, 'yellow');
    }

    /**
     * Write an error msg
     *
     * @param string $msg
     * @return void
     */
    public function writeError($msg)
    {
        $this->writeLn("[ERROR] - ".$msg, 'red');
    }

    /**
     * Write a header
     *
     * @param string $title
     * @return void
     */
    public function writeHeader($title)
    {
        $this->writeLn("\n#################################################################", "green");
        $this->writeLn("\t$title", "green");
        $this->writeLn("#################################################################", "green");
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

    /**
     * Get all available Import Providers
     *
     * @return ImportProviderInterface[]
     */
    protected function getImportProviders()
    {
        $providers = [];
        $installed = QUI::getPackageManager()->getInstalled();

        foreach ($installed as $package) {
            try {
                $Package = QUI::getPackage($package['name']);

                if (!$Package->isQuiqqerPackage()) {
                    continue;
                }

                $importProviderClasses = $Package->getProvider('cms-import');

                /** @var ImportProviderInterface $class */
                foreach ($importProviderClasses as $class) {
                    $ImportProvider = new $class($this);

                    if ($ImportProvider instanceof ImportProviderInterface) {
                        $providers[] = $ImportProvider;
                    }
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $providers;
    }
}
