<?php

namespace QUI\CmsImport\Hierarchy;

class SiteItem extends HierarchyItem
{
    /**
     * @var bool
     */
    protected $isLink;

    /**
     * HierarchyItem constructor.
     *
     * @param int|string $siteIdentifier - Site identifier
     * @param int|string $parentIdentifier - Parent Site identifier
     * @param bool $isLink (optional) - Is this Site a link (instead of an original site)? [default: false]
     * @return void
     */
    public function __construct($siteIdentifier, $parentIdentifier = null, $isLink = false)
    {
        $this->isLink = $isLink;

        parent::__construct($siteIdentifier, $parentIdentifier);
    }

    /**
     * @return bool
     */
    public function isLink()
    {
        return $this->isLink;
    }
}
