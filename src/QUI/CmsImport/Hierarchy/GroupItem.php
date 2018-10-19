<?php

namespace QUI\CmsImport\Hierarchy;

class GroupItem extends HierarchyItem
{
    /**
     * GroupItem constructor.
     *
     * @param int|string $groupIdentiier - Unique ImportGroup identifier
     * @param int|string $parentIdentifier (optional) - Unique ImportGroup identifier of parent group
     * @return void
     */
    public function __construct($groupIdentiier, $parentIdentifier = null)
    {
        parent::__construct($groupIdentiier, $parentIdentifier);
    }
}
