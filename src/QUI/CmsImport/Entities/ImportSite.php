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
     * @var string
     */
    protected $reviewText = '';

    /**
     * @var int
     */
    protected $quiqqerSiteId = null;

    /**
     * ImportProject constructor.
     *
     * @param string $project - The name of the project this Site belongs to
     * @param string $name - Name of the Site (this is the part that is seen in the URL)
     * @param string $lang - Language of the site
     * @param string|int $siteIdentifier - Internal unique identification string for this ImportSite (import module specific!)
     * @param array $attributes (optional) - Additional Site attributes
     */
    public function __construct($project, $lang, $name, $siteIdentifier, $attributes = [])
    {
        $this->project        = $project;
        $this->name           = $name;
        $this->lang           = $lang;
        $this->siteIdentifier = $siteIdentifier;

        $this->setAttributes(array_merge($attributes, [
            'name' => $this->name
        ]));
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
        $this->tags = $tags;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
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
     * Flag this site for review, so it is listed in a special "todo.log" after import
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
