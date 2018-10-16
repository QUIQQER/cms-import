<?php

namespace QUI\CmsImport\Hierarchy;

class SiteHierarchy extends Hierarchy
{
    /**
     * @param HierarchyItem $Item - Must be of instance \QUI\CmsImport\Hierarchy\SiteItem
     * @return void
     */
    public function addChild(HierarchyItem $Item)
    {
        if ($Item instanceof SiteItem) {
            parent::addChild($Item);
        }
    }
}
