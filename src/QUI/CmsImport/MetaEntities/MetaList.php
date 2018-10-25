<?php

namespace QUI\CmsImport\MetaEntities;

/**
 * Class MetaItemList
 *
 * A simple list of MetaEntity objects
 */
class MetaList implements ChildrenInterface
{
    /**
     * @var MetaEntity[]
     */
    protected $children = [];

    /**
     * Add a child to the list
     *
     * @param MetaEntity $Item
     * @return void
     */
    public function addChild(MetaEntity $Item)
    {
        $this->children[] = $Item;
    }

    /**
     * Get a child from the list
     *
     * @param int|string $id
     * @return bool|MetaEntity
     */
    public function getChild($id)
    {
        /** @var MetaEntity $Child */
        foreach ($this->walkChildren() as $Child) {
            if ($Child->getId() == $id) {
                return $Child;
            }
        }

        return false;
    }

    /**
     * @return MetaEntity[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Walk the list of children
     *
     * @return \Generator
     */
    public function walkChildren()
    {
        foreach ($this->children as $k => $Child) {
            yield $k => $Child;
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
