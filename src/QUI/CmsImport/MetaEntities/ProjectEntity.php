<?php

namespace QUI\CmsImport\MetaEntities;

class ProjectEntity extends MetaEntity
{
    /**
     * @var array
     */
    protected $languages = [];

    /**
     * @var string
     */
    protected $defaultLanguage = null;

    /**
     * @var bool
     */
    protected $default = false;

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * Set project default status
     *
     * @param bool $default
     */
    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }

    /**
     * @param string $lang
     * @param bool $default (optional) - Default project language? [default: false]
     */
    public function addLanguage($lang, $default = false)
    {
        $this->languages[] = $lang;

        if ($default) {
            $this->defaultLanguage = $lang;
        }
    }

    /**
     * @return array
     */
    public function getLanguages(): array
    {
        return array_values(array_unique($this->languages));
    }

    /**
     * @return string
     */
    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
    }
}
