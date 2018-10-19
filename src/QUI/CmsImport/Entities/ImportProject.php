<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportProject
 *
 * Represents a QUIQQER project that is imported
 */
class ImportProject extends AbstractImportEntity
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $defaultLang;

    /**
     * @var array
     */
    protected $langs = [];

    /**
     * @var string|int
     */
    protected $identifier;

    /**
     * ImportProject constructor.
     *
     * @param string|int $identifier - Project identifier
     * @param string $name - Project name
     * @param string $lang - Project standard language
     * @param array $languages (optional) - All available Project languages
     * @param array $attributes (optional) - Additional attributes
     */
    public function __construct($identifier, $name, $lang, $languages = [], $attributes = [])
    {
        $this->identifier  = $identifier;
        $this->name        = $name;
        $this->defaultLang = $lang;
        $this->langs       = $languages;

        $this->setAttributes($attributes);
    }

    /**
     * @return int|string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getDefaultLang()
    {
        return $this->defaultLang;
    }

    /**
     * @return array
     */
    public function getLangs()
    {
        return $this->langs;
    }
}
