<?php

namespace QUI\CmsImport\MetaEntities;

interface ChildrenInterface
{
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
}
