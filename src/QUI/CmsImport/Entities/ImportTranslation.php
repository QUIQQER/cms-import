<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportTranslation
 *
 * Represents a QUIQQER translation for group [project/{IMPORT_PROJECT}]
 */
class ImportTranslation extends AbstractImportEntity
{
    protected $group;
    protected $var;
    protected $translations = [];

    /**
     * ImportTranslation constructor.
     *
     * @param string $var - Translation variable
     */
    public function __construct($var)
    {
        $this->var = $var;
    }

    /**
     * Set translation text for a language
     *
     * @param string $lang
     * @param string $translation
     * @return void
     */
    public function setTranslationForLanguage($lang, $translation)
    {
        $this->translations[$lang] = $translation;
    }

    /**
     * @return array
     */
    public function getTranslations()
    {
        return $this->translations;
    }
}
