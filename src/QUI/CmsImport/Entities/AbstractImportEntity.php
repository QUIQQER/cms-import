<?php

namespace QUI\CmsImport\Entities;

use QUI;

abstract class AbstractImportEntity extends QUI\QDOM
{
    /**
     * @var array
     */
    protected $reviewFlags = [];

    /**
     * Flag this entity for review, so it is listed in a special "todo.log" after import
     *
     * @param string $reason (optional) - The reaseon why this site is flagged for review
     */
    public function addReviewFlag($reason = '')
    {
        $this->reviewFlags[] = $reason;
    }

    /**
     * @return array
     */
    public function getReviewFlags()
    {
        return $this->reviewFlags;
    }
}
