<?php

namespace QUI\CmsImport\Entities;

use QUI;

abstract class AbstractImportEntity extends QUI\QDOM
{
    /**
     * @var string|int
     */
    protected $identifier;

    /**
     * @var array
     */
    protected $reviewFlags = [];

    /**
     * AbstractImportEntity constructor.
     * @param $identifier - Unique identifier for this ImportEntitiy
     */
    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

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

    /**
     * @return bool
     */
    public function hasReviewFlags()
    {
        return !empty($this->reviewFlags);
    }

    /**
     * @return int|string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Get the import section the ImportEntitiy belongs to
     *
     * @return string
     */
    abstract public function getImportSection();
}
