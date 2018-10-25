<?php

namespace QUI\CmsImport\MetaEntities;

class SiteHierarchy extends MetaHierarchy
{
    /**
     * Add a child to the list
     *
     * @param MetaEntity $Item
     * @return void
     */
    public function addChild(MetaEntity $Item)
    {
        if ($Item instanceof SiteEntity) {
            parent::addChild($Item);
        }
    }
}
