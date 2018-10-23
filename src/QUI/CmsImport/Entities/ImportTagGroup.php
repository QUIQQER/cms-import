<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportTagGroup
 *
 * Represents a QUIQQER tag group that is imported
 */
class ImportTagGroup extends AbstractImportEntity
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var int
     */
    protected $priority;

    /**
     * ImportProject constructor.
     *
     * @param string|int $identifier - ImportTagGroup identifier
     * @param string $title - Tag title
     */
    public function __construct($identifier, $title)
    {
        $this->title = $title;
        parent::__construct($identifier);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * Get the import section the ImportEntitiy belongs to
     *
     * @return string
     */
    public function getImportSection()
    {
        return QUI\CmsImport\Import::IMPORT_SECTION_TAGS;
    }
}
