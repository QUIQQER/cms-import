<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportSite
 *
 * Represents a QUIQQER site that is imported
 */
class ImportSite extends AbstractImportEntity implements CustomQuiqqerIdInterface
{
    /**
     * Collection of tag titles associated with the Site
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Collection of tag group titles and their associated tag titles
     *
     * @var array
     */
    protected $tagGroups = [];

    /**
     * List of language links
     *
     * @var array
     */
    protected $languageLinks = [];

    /**
     * @var string|int
     */
    protected $projectIdentifier;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $lang;

    /**
     * @var bool
     */
    protected $isFlaggedForReview = false;

    /**
     * @var int
     */
    protected $quiqqerId = null;

    /**
     * ImportProject constructor.
     *
     * @param string|int $identifier - Unique identifier for this ImportSite
     * @param string|int $projectIdentifier - The name of the project this Site belongs to
     * @param string $name - Name of the Site (this is the part that is seen in the URL)
     * @param string $lang - Language of the site
     * @param array $attributes (optional) - Additional Site attributes
     */
    public function __construct($identifier, $projectIdentifier, $lang, $name, $attributes = [])
    {
        $this->projectIdentifier = $projectIdentifier;
        $this->name              = $name;
        $this->lang              = $lang;

        // Default values
        $this->setAttributes([
            'type' => 'standard'
        ]);

        $this->setAttributes(array_merge($attributes, [
            'name' => $this->name
        ]));

        parent::__construct($identifier);
    }

    /**
     * Set QUIQQER ID
     *
     * @param int $id
     * @return void
     */
    public function setQuiqqerId(int $id)
    {
        $this->quiqqerId = $id;
    }

    /**
     * Return QUIQQER ID
     *
     * @return int|null
     */
    public function getQuiqqerId()
    {
        return $this->quiqqerId;
    }

    /**
     * @return string|int
     */
    public function getProjectIdentifier()
    {
        return $this->projectIdentifier;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param string $title - Simple text only
     */
    public function setTitle($title)
    {
        $this->setAttribute('title', $title);
    }

    /**
     * @param string $short - Simple text only
     */
    public function setShort($short)
    {
        $this->setAttribute('short', $short);
    }

    /**
     * @param string $content - Text which may contain HTML
     */
    public function setContent($content)
    {
        $this->setAttribute('content', $content);
    }

    /**
     * @param string $siteType
     */
    public function setSiteType($siteType)
    {
        $this->setAttribute('type', $siteType);
    }

    /**
     * Add a tag group that is associated with this Site
     *
     * @param string $tagGroupIdentifier
     * @return void
     */
    public function addTagGroup($tagGroupIdentifier)
    {
        $this->tagGroups[] = $tagGroupIdentifier;
    }

    /**
     * Add a tag that is associated with this Site
     *
     * @param string $tagIdentifier
     * @return void
     */
    public function addTag($tagIdentifier)
    {
        $this->tags[] = $tagIdentifier;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return array_values(array_unique($this->tags));
    }

    /**
     * @return array
     */
    public function getTagGroups()
    {
        return array_values(array_unique($this->tagGroups));
    }

    /**
     * Set a language link to a Site in another language
     *
     * @param string $lang
     * @param int $siteIdentifier - ImportSite identifier
     */
    public function setLanguageLink($lang, $siteIdentifier)
    {
        $this->languageLinks[$lang] = $siteIdentifier;
    }

    /**
     * @return array
     */
    public function getLanguageLinks()
    {
        return $this->languageLinks;
    }

    /**
     * Get the import section the ImportEntitiy belongs to
     *
     * @return string
     */
    public function getImportSection()
    {
        return QUI\CmsImport\Import::IMPORT_SECTION_SITES;
    }
}
