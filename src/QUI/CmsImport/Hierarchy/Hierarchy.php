<?php

namespace QUI\CmsImport\Hierarchy;

class Hierarchy implements ChildrenIteratorInterface
{
    /**
     * @var HierarchyItem[]
     */
    protected $children = [];

    /**
     * @var bool
     */
    protected $sorted = false;

    public function addChild(HierarchyItem $Item)
    {
        $this->children[] = $Item;
        $this->sorted     = false;
    }

    /**
     * Build the complete tree out of all children
     *
     * @return void
     */
    public function buildTree()
    {
        /** @var HierarchyItem $Child */
        foreach ($this->children as $k => $Child) {
            if (!$Child->getParentId()) {
                continue;
            }

            $ParentChild = $this->getChild($Child->getParentId());

            if ($ParentChild) {
                $ParentChild->addChild($Child);
                unset($this->children[$k]);
            }
        }

        $this->sorted = true;
    }

    /**
     * Get a child from the tree
     *
     * @param int|string $id
     * @return bool|HierarchyItem
     */
    protected function getChild($id)
    {
        /** @var HierarchyItem $Child */
        foreach ($this->walkTree($this->children) as $Child) {
            if ($Child->getId() === $id) {
                return $Child;
            }
        }

        return false;
    }

    /**
     * Walk the tree of children
     *
     * @return \Generator
     */
    public function walkChildren()
    {
        if (!$this->sorted) {
            $this->buildTree();
        }

        foreach ($this->children as $k => $Child) {
            yield $k => $Child;
        }
    }

    /**
     * Walk the tree of children
     *
     * @param HierarchyItem[] $children
     * @return \Generator
     */
    protected function walkTree($children)
    {
        foreach ($children as $k => $Child) {
            yield $k => $Child;

            if ($Child->hasChildren()) {
                yield from $this->walkTree($Child->getChildren());
            }
        }
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }
}
