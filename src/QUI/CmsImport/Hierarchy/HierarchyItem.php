<?php

namespace QUI\CmsImport\Hierarchy;

use QUI;

class HierarchyItem extends QUI\QDOM implements ChildrenIteratorInterface
{
    /**
     * @var int|string
     */
    protected $id;

    /**
     * @var int|string
     */
    protected $parentId = null;

    /**
     * @var HierarchyItem[]
     */
    protected $children = [];

    /**
     * HierarchyItem constructor.
     *
     * @param int|string $id
     * @param int|string $parentId (optional)
     */
    public function __construct($id, $parentId = null)
    {
        $this->id       = $id;
        $this->parentId = $parentId;
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int|string
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Add a child to this Item
     *
     * Multiple children with the same ID are not allowed
     *
     * @param HierarchyItem $Item
     * @return void
     */
    final public function addChild(HierarchyItem $Item) {
        if (!$this->getChild($Item->getId())) {
            $this->children[] = $Item;
        }
    }

    /**
     * Get child by ID
     *
     * @param int|string $id
     * @return HierarchyItem|false - Item or false if not found
     */
    final public function getChild($id)
    {
        foreach ($this->children as $Child) {
            if ($Child->getId() === $id) {
                return $Child;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    final public function hasChildren() {
        return !empty($this->children);
    }

    /**
     * Walk all DIRECT children
     *
     * @return \Generator
     */
    final public function walkChildren()
    {
        foreach ($this->children as $Child) {
            yield $Child;
        }
    }

    /**
     * @return HierarchyItem[]
     */
    final public function getChildren() {
        return $this->children;
    }
}
