<?php

namespace QUI\CmsImport\Hierarchy;

class TagGroupItem extends HierarchyItem
{
    /**
     * TagGroupItem constructor.
     *
     * @param int|string $tagGroupIdentifier - Unique ImportTagGroup identifier
     * @param int|string $parentIdentifier - Unique ImportTagGroup identifier of parent media item
     * @return void
     */
    public function __construct($tagGroupIdentifier, $parentIdentifier = null)
    {
        parent::__construct($tagGroupIdentifier, $parentIdentifier);
    }
}
