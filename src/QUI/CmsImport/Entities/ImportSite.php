<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportSite
 *
 * Represents a QUIQQER site that is imported
 */
class ImportSite extends AbstractImportEntity
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
     * @var string|int
     */
    protected $siteIdentifier;

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
    protected $quiqqerSiteId = null;

    /**
     * ImportProject constructor.
     *
     * @param string|int $projectIdentifier - The name of the project this Site belongs to
     * @param string $name - Name of the Site (this is the part that is seen in the URL)
     * @param string $lang - Language of the site
     * @param string|int $siteIdentifier - Internal unique identification string for this ImportSite (import module specific!)
     * @param array $attributes (optional) - Additional Site attributes
     */
    public function __construct($projectIdentifier, $lang, $name, $siteIdentifier, $attributes = [])
    {
        $this->projectIdentifier = $projectIdentifier;
        $this->name              = $name;
        $this->lang              = $lang;
        $this->siteIdentifier    = $siteIdentifier;

        $this->setAttributes(array_merge($attributes, [
            'name' => $this->name
        ]));
    }

    /**
     * @return int|string
     */
    public function getIdentifier()
    {
        return $this->siteIdentifier;
    }

    /**
     * @return int|null
     */
    public function getQuiqqerSiteId()
    {
        return $this->quiqqerSiteId;
    }

    /**
     * Set a custom QUIQQER site id
     *
     * The import process will try to assign this ID to the QUIQQER site
     *
     * @param int $quiqqerSiteId
     */
    public function setQuiqqerSiteId($quiqqerSiteId)
    {
        $this->quiqqerSiteId = $quiqqerSiteId;
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
     * @param $title
     * @return void
     */
    public function addTagGroup($title)
    {
        $this->tagGroups[] = $title;
    }

    /**
     * Add a tag that is associated with this Site
     *
     * @param string $title
     * @return void
     */
    public function addTag($title)
    {
        $this->tags[] = $title;
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
}
