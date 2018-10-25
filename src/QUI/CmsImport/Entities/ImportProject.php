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
    protected $lang;

    /**
     * ImportProject constructor.
     *
     * @param string|int $identifier - Project identifier
     * @param string $name - Project name
     * @param array $attributes (optional) - Additional attributes
     */
    public function __construct($identifier, $name, $attributes = [])
    {
        $this->identifier = $identifier;
        $this->name       = $name;

        $this->setAttributes($attributes);
        parent::__construct($identifier);
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
     * Get the import section the ImportEntitiy belongs to
     *
     * @return string
     */
    public function getImportSection()
    {
        return QUI\CmsImport\Import::IMPORT_SECTION_PROJECTS;
    }
}
