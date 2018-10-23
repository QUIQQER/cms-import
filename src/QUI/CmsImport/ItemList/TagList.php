<?php

namespace QUI\CmsImport\ItemList;

class TagList extends ChildrenList
{
    /**
     * Add a child to the list
     *
     * @param ListItem $Item - Must be instance of \QUI\CmsImport\ItemList\TagItem
     * @return void
     */
    public function addChild(ListItem $Item)
    {
        if ($Item instanceof TagItem) {
            parent::addChild($Item);
        }
    }
}
