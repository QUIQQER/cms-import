<?php

namespace QUI\CmsImport\Hierarchy;

class GroupHierarchy extends Hierarchy
{
    /**
     * @param HierarchyItem $Item - Must be of instance \QUI\CmsImport\Hierarchy\GroupItem
     * @return void
     */
    public function addChild(HierarchyItem $Item)
    {
        if ($Item instanceof GroupItem) {
            parent::addChild($Item);
        }
    }
}
