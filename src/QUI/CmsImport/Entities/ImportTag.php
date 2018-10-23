<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportTag
 *
 * Represents a QUIQQER tag group that is imported
 */
class ImportTag extends AbstractImportEntity
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string - ImportTagGroup identifier
     */
    protected $tagGroup;

    /**
     * ImportProject constructor.
     *
     * @param string|int $identifier - ImportTagGroup identifier
     * @param string $name - Tag name
     */
    public function __construct($identifier, $name)
    {
        $this->name = $name;
        parent::__construct($identifier);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
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
     * @return string
     */
    public function getTagGroup()
    {
        return $this->tagGroup;
    }

    /**
     * @param string $tagGroup
     */
    public function setTagGroup($tagGroup)
    {
        $this->tagGroup = $tagGroup;
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
