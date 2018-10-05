<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportSite
 *
 * Represents a QUIQQER site that is imported
 */
class ImportSite extends QUI\QDOM
{
    /**
     * List of tag titles associated with the Site
     *
     * @var array
     */
    protected $tags = [];

    /**
     * List of language links
     *
     * @var array
     */
    protected $languageLinks = [];

    /**
     * ImportProject constructor.
     *
     * @param string $project - The name of the project this Site belongs to
     * @param string $name - Name of the Site (this is the part that is seen in the URL)
     * @param int $id (optional) - Provide an ID if the Site should have a fixed ID
     * @param array $attributes (optional) - Additional Site attributes
     */
    public function __construct($project, $name, $id = null, $attributes = [])
    {
        $this->setAttributes(array_merge(
            $attributes,
            [
                'project' => $project,
                'name'    => $name,
                'id'      => $id
            ]
        ));
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
     * Set tags
     *
     * @param array $tags - Tag titles (not QUIQQER internal tag names!)
     */
    public function setTags($tags)
    {
        $this->setAttribute('tags', $tags);
    }

    /**
     * Set a language link to a Site in another language
     *
     * @param string $lang
     * @param int $siteId
     */
    public function setLanguageLink($lang, $siteId)
    {
        $this->languageLinks[$lang] = $siteId;
    }

    /**
     * @return array
     */
    public function getLanguageLinks()
    {
        return $this->languageLinks;
    }
}
