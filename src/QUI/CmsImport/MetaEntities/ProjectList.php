<?php

namespace QUI\CmsImport\MetaEntities;

class ProjectList extends MetaList
{
    /**
     * Add a child to the list
     *
     * @param MetaEntity $Item
     * @return void
     */
    public function addChild(MetaEntity $Item)
    {
        if ($Item instanceof ProjectEntity) {
            parent::addChild($Item);
        }
    }
}
