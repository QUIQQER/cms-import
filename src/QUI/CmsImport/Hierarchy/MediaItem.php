<?php

namespace QUI\CmsImport\Hierarchy;

class MediaItem extends HierarchyItem
{
    /**
     * MediaItem constructor.
     *
     * @param int|string $mediaItemIdentifier - Unique ImportMediaItem identifier
     * @param int|string $parentIdentifier - Unique ImportMediaItem identifier of parent media item
     * @return void
     */
    public function __construct($mediaItemIdentifier, $parentIdentifier = null)
    {
        parent::__construct($mediaItemIdentifier, $parentIdentifier);
    }
}
