<?php

namespace QUI\CmsImport\ItemList;

class UserList extends ChildrenList
{
    /**
     * Add a child to the list
     *
     * @param ListItem $Item - Must be instance of \QUI\CmsImport\ItemList\UserItem
     * @return void
     */
    public function addChild(ListItem $Item)
    {
        if ($Item instanceof UserItem) {
            parent::addChild($Item);
        }
    }
}
