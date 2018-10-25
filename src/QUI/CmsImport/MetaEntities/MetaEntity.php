<?php

namespace QUI\CmsImport\MetaEntities;

use QUI;

class MetaEntity extends QUI\QDOM implements ChildrenInterface
{
    /**
     * @var int|string
     */
    protected $id;

    /**
     * @var int|string
     */
    protected $parentId;

    /**
     * @var MetaList
     */
    protected $ChildrenList;

    /**
     * HierarchyItem constructor.
     *
     * @param int|string $id - Unique identifier
     * @param int|string $parentId (optional) - Unique identifier of parent MetaHierarchyItem
     */
    public function __construct($id, $parentId = null)
    {
        $this->id           = $id;
        $this->ChildrenList = new MetaList();
        $this->parentId     = $parentId;
    }

    /**
     * @return MetaList
     */
    public function getChildrenList(): MetaList
    {
        return $this->ChildrenList;
    }

    /**
     * Add a child to the list
     *
     * @param MetaEntity $Item
     * @return void
     */
    public function addChild(MetaEntity $Item)
    {
        $this->ChildrenList->addChild($Item);
    }

    /**
     * Get a child from the list
     *
     * @param int|string $id
     * @return bool|MetaEntity
     */
    protected function getChild($id)
    {
        return $this->ChildrenList->getChild($id);
    }

    /**
     * Does this MetaItem have children?
     *
     * @return bool
     */
    public function hasChildren()
    {
        return $this->ChildrenList->hasChildren();
    }

    /**
     * Walk the list of children
     *
     * @return \Generator
     */
    public function walkChildren()
    {
        return $this->ChildrenList->walkChildren();
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int|string|null
     */
    public function getParentId()
    {
        return $this->parentId;
    }
}
