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
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $image = null;

    /**
     * @var string[] - ImportTagGroup identifiers
     */
    protected $tagGroups = [];

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
     * @return string[]
     */
    public function getTagGroups()
    {
        return array_values(array_unique($this->tagGroups));
    }

    /**
     * @param string $tagGroup
     */
    public function addTagGroup($tagGroup)
    {
        $this->tagGroups[] = $tagGroup;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image - QUIQQER Image URL
     */
    public function setImage(string $image)
    {
        $this->image = $image;
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
