<?php

namespace QUI\CmsImport\MetaEntities;

interface ChildrenInterface
{
    const SORT_TYPE_ASC = 'ASC';
    const SORT_TYPE_DESC = 'DESC';

    /**
     * Walk the tree of children
     *
     * @return \Generator
     */
    public function walkChildren();

    /**
     * @return bool
     */
    public function hasChildren();

    /**
     * @param string $attribute - Name of the attribute that is sorted by
     * @param string $type - Type of sort (see ChildrenInterface::SORT_TYPE_*)
     * @return void
     */
    public function sortChildren($attribute, $type = self::SORT_TYPE_ASC);

    /**
     * Sort children by a custom sort function
     *
     * @param callable $sortFunc
     * @return void
     */
    public function sortChildrenCustom(callable $sortFunc);
}
