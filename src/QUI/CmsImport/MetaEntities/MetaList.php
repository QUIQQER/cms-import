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

    /**
     * @param string $attribute - Name of the attribute that is sorted by
     * @param string $type - Type of sort (see ChildrenInterface::SORT_TYPE_*)
     * @return void
     */
    public function sortChildren($attribute, $type = self::SORT_TYPE_ASC)
    {
        usort($this->children, function ($ChildA, $ChildB) use ($attribute, $type) {
            /**
             * @var MetaEntity $ChildA
             * @var MetaEntity $ChildB
             */
            $valA = $ChildA->getAttribute($type);
            $valB = $ChildB->getAttribute($type);

            // Numeric comparison
            if ((\is_int($valA) || \is_float($valA)) && (\is_int($valB) || \is_float($valB))) {
                if ($valA < $valB) {
                    switch ($type) {
                        case self::SORT_TYPE_DESC:
                            return 1;
                            break;

                        default:
                            return -1;
                    }
                }

                if ($valA > $valB) {
                    switch ($type) {
                        case self::SORT_TYPE_DESC:
                            return -1;
                            break;

                        default:
                            return 1;
                    }
                }

                return 0;
            }

            // String comparison
            switch ($type) {
                case self::SORT_TYPE_DESC:
                    return \strcmp($valA, $valB) * (-1);
                    break;

                default:
                    return \strcmp($valA, $valB);
            }
        });
    }

    /**
     * Sort children by a custom sort function
     *
     * @param callable $sortFunc
     * @return void
     */
    public function sortChildrenCustom(callable $sortFunc)
    {
        usort($this->children, $sortFunc);
    }
}
