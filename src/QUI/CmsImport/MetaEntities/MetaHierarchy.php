<?php

namespace QUI\CmsImport\MetaEntities;

class MetaHierarchy extends MetaList
{
    /**
     * @var bool
     */
    protected $sorted = false;

    /**
     * Add a child to the list
     *
     * @param MetaEntity $Item
     * @return void
     */
    public function addChild(MetaEntity $Item)
    {
        $this->sorted = false;
        parent::addChild($Item);
    }

    /**
     * Build the complete tree out of all children
     *
     * @return void
     */
    public function buildTree()
    {
        // Children that are deleted from root
        $deleteKeys = [];

        /** @var MetaEntity $Child */
        foreach ($this->children as $k => $Child) {
            if (!$Child->getParentId()) {
                continue;
            }

            $ParentChild = $this->getChild($Child->getParentId());

            if ($ParentChild) {
                $ParentChild->addChild($Child);
                $deleteKeys[] = $k;
            }
        }

        foreach ($deleteKeys as $deleteKey) {
            unset($this->children[$deleteKey]);
        }

        $this->sorted = true;
    }

    /**
     * Get a child from the tree
     *
     * @param int|string $id
     * @return bool|MetaEntity
     */
    public function getChild($id)
    {
        /** @var MetaEntity $Child */
        foreach ($this->walkTree($this->children) as $Child) {
            if ($Child->getId() == $id) {
                return $Child;
            }
        }

        return false;
    }

    /**
     * Walk the list of children
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
     * @param MetaEntity[] $children
     * @return \Generator
     */
    protected function walkTree($children)
    {
        foreach ($children as $k => $Child) {
            yield $k => $Child;

            if ($Child->hasChildren()) {
                yield from $this->walkTree($Child->getChildrenList()->getChildren());
            }
        }
    }
}
