<?php

namespace QUI\CmsImport\Hierarchy;

class MediaItemHierarchy extends Hierarchy
{
    /**
     * @param HierarchyItem $Item - Must be of instance \QUI\CmsImport\Hierarchy\MediaItem
     * @return void
     */
    public function addChild(HierarchyItem $Item)
    {
        if ($Item instanceof MediaItem) {
            parent::addChild($Item);
        }
    }
}
