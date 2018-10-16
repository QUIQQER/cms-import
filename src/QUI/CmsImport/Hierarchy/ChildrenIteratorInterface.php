<?php

namespace QUI\CmsImport\Hierarchy;

interface ChildrenIteratorInterface
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
