<?php

namespace QUI\CmsImport\MetaEntities;

use QUI\Projects\Site;

class SiteHierarchy extends MetaHierarchy
{
    /**
     * Add a child to the list
     *
     * @param MetaEntity $Item
     * @return void
     */
    public function addChild(MetaEntity $Item)
    {
        if ($Item instanceof SiteEntity) {
            parent::addChild($Item);
        }
    }

    /**
     * Get a child from the tree
     *
     * @param int|string $id
     * @param bool $noLink (optional) - Return non-link entity only
     * @return bool|MetaEntity
     */
    public function getChild($id, $noLink = false)
    {
        /** @var MetaEntity $Child */
        foreach ($this->walkTree($this->children) as $Child) {
            if ($Child->getId() != $id) {
                continue;
            }

            if ($noLink && $Child instanceof SiteEntity && $Child->isLink()) {
                continue;
            }

            return $Child;
        }

        return false;
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

            $noLink = false;

            if ($Child instanceof SiteEntity && !$Child->isLink()) {
                $noLink = true;
            }

            $ParentChild = $this->getChild($Child->getParentId(), $noLink);

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
}
