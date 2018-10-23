<?php

namespace QUI\CmsImport\Hierarchy;

class TagGroupHierarchy extends Hierarchy
{
    /**
     * @param HierarchyItem $Item - Must be of instance \QUI\CmsImport\Hierarchy\TagGroupItem
     * @return void
     */
    public function addChild(HierarchyItem $Item)
    {
        if ($Item instanceof TagGroupItem) {
            parent::addChild($Item);
        }
    }
}
