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
     *
     * @throws \Exception
     */
    public function execute()
    {
        $L  = QUI::getLocale();
        $lg = 'quiqqer/cms-import';

        $importLang = $this->writePrompt('Import language? (en/de)', 'en');
        $L->setCurrent($importLang);

        $rootUserId = (int)QUI::conf('globals', 'rootuser');

        if (QUI::getUserBySession()->getId() !== $rootUserId) {
            $this->exitFail($L->get($lg, 'error.root_user_only'));
        }

        if (!$this->isSystemEmpty()) {
            $this->writeWarning($L->get($lg, 'warning.system_not_clean'));
        }

        $userInput = $this->writePrompt($L->get($lg, 'prompt.cleanup'), 'y');

        if (mb_strtolower($userInput) === 'n') {
            $cleanup = false;
        } else {
            $cleanup = true;
        }

        // Choose ImportProvider
        do {
            $this->writeHeader($L->get($lg, 'header.provider_selection'));
            $this->writeLn("");

            $providers = $this->getImportProviders();

            foreach ($providers as $k => $ImportProvider) {
                $this->writeLn("[".($k + 1)."] ".$ImportProvider->getTitle()." - ".$ImportProvider->getDescription(), 'blue');
            }

            $this->writeLn("");
            $key = $this->writePrompt($L->get($lg, 'prompt.provider_select'));

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

        $importSettings = [
            'cleanup' => $cleanup
        ];

        $importAreas = [
            'importSites',
            'importTags',
            'importMedia',
            'importUsers',
            'importGroups',
            'importSystemConfig',
            'importTranslations',
            'importPermissions'
        ];

        $this->writeHeader($L->get($lg, 'import.header.areas'));

        foreach ($importAreas as $area) {
            $prompt = $this->writePrompt($L->get($lg, 'import.setting.area.'.$area), 'y');

            if (mb_strtolower($prompt) !== 'n') {
                $importSettings[$area] = true;
            }
        }

        $Import = new Import($SelectedProvider, $importSettings);
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
            $this->writeLn("[?] - ".$msg.": ", 'white');
        } else {
            $this->writeLn("[?] - ".$msg." [".$defaultValue."]: ", 'white');
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
