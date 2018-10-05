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
     * Import constructor.
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        $this->setAttributes([
            'cleanup' => true
        ]);

        $this->setAttributes($settings);
    }

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

                $list = array_merge($list, $Package->getProvider('auth'));
            } catch (QUI\Exception $exception) {
            }
        }
    }
}
