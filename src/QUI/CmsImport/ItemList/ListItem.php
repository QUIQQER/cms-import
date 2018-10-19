<?php

namespace QUI\CmsImport\ItemList;

use QUI;

class ListItem extends QUI\QDOM
{
    /**
     * @var int|string
     */
    protected $id;

    /**
     * HierarchyItem constructor.
     *
     * @param int|string $id - Unique identifier
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }
}
