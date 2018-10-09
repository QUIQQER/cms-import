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
     * @var string
     */
    protected $project;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int|null
     */
    protected $id;

    /**
     * @var false|int
     */
    protected $parentId;

    /**
     * @var string
     */
    protected $lang;

    /**
     * @var bool
     */
    protected $isFlaggedForReview = false;

    /**
     * @var string
     */
    protected $reviewText = '';

    /**
     * ImportProject constructor.
     *
     * @param string $project - The name of the project this Site belongs to
     * @param string $name - Name of the Site (this is the part that is seen in the URL)
     * @param string $lang - Language of the site
     * @param int|false $parentId - If this is FALSE this Site is supposed to be the root site!
     * @param int $id (optional) - Provide an ID if the Site should have a fixed ID
     * @param array $attributes (optional) - Additional Site attributes
     */
    public function __construct($project, $lang, $name, $parentId, $id = null, $attributes = [])
    {
        $this->project  = $project;
        $this->name     = $name;
        $this->lang     = $lang;
        $this->id       = $id;
        $this->parentId = $parentId;

        $this->setAttributes($attributes);
    }

    /**
     * @return string
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int|false
     */
    public function getParentId()
    {
        return $this->parentId;
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

    /**
     * Flag this site for review, so it is listed in a special "todo.txt" after import
     *
     * @param string $reason (optional) - The reaseon why this site is flagged for review
     */
    public function flagForReview($reason = '')
    {
        $this->isFlaggedForReview = true;
        $this->reviewText         = $reason;
    }

    /**
     * @return bool
     */
    public function isFlaggedForReview()
    {
        return $this->isFlaggedForReview;
    }

    /**
     * @return string
     */
    public function getReviewText()
    {
        return $this->reviewText;
    }
}
