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
    const DATATYPE_JS     = 'js';
    const DATATYPE_PHP    = 'php';
    const DATATYPE_PHP_JS = 'php,js';

    /**
     * @var string
     */
    protected $var;

    /**
     * @var array
     */
    protected $translations = [];

    /**
     * @var string
     */
    protected $datatype;

    /**
     * @var bool
     */
    protected $isHtml = false;

    /**
     * ImportTranslation constructor.
     *
     * @param string $identifier - Unique identifier for this ImportTranslation
     * @param string $var - Translation variable
     * @param string $datatype - Translation datatype (one of self::DATATYPE_*)
     * @param bool $html - Does translation contain HTML?
     */
    public function __construct($identifier, $var, $datatype = self::DATATYPE_PHP_JS, $html = false)
    {
        $this->var      = $var;
        $this->datatype = $datatype;
        $this->isHtml   = $html;
        parent::__construct($identifier);
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
     * @return string
     */
    public function getVar()
    {
        return $this->var;
    }

    /**
     * @return array
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @return string
     */
    public function getDatatype()
    {
        return $this->datatype;
    }

    /**
     * @return bool
     */
    public function isHtml()
    {
        return $this->isHtml;
    }

    /**
     * Get the import section the ImportEntitiy belongs to
     *
     * @return string
     */
    public function getImportSection()
    {
        return QUI\CmsImport\Import::IMPORT_SECTION_TRANSLATIONS;
    }
}
