<?php

namespace QUI\CmsImport\ItemList;

use QUI\CmsImport\Hierarchy\ChildrenIteratorInterface;

/**
 * Class ChildrenList
 *
 * A simple list of ListItems
 */
class ChildrenList implements ChildrenIteratorInterface
{
    /**
     * @var ListItem[]
     */
    protected $children = [];

    /**
     * Add a child to the list
     *
     * @param ListItem $Item
     * @return void
     */
    public function addChild(ListItem $Item)
    {
        $this->children[] = $Item;
    }

    /**
     * Get a child from the list
     *
     * @param int|string $id
     * @return bool|ListItem
     */
    protected function getChild($id)
    {
        /** @var ListItem $Child */
        foreach ($this->walkChildren() as $Child) {
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
