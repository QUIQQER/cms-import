<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportMediaItem
 *
 * Represents a QUIQQER media item that is imported
 */
class ImportMediaItem extends AbstractImportEntity
{
    const MEDIA_TYPE_IMAGE  = 'image';
    const MEDIA_TYPE_FILE   = 'file';
    const MEDIA_TYPE_FOLDER = 'folder';

    /**
     * @var int|string
     */
    protected $identifier;

    /**
     * @var int|string
     */
    protected $projectIdentifier;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $title;

//    protected $short = null;
//    protected $active = false;
//    protected $createDate = null;
//    protected $altText = null;
//    protected $mimeType = null;
//    protected $imageHeight = null;
//    protected $imageWidth = null;
//    protected $roundCorners = null;


    /**
     * @var int
     */
    protected $quiqqerMediaId = null;

    /**
     * @var string - Absolute file path to associated file
     */
    protected $file = null;

    /**
     * ImportProject constructor.
     *
     * @param string|int $identifier - Unique ImportMediaItem identifier
     * @param string|int $projectIdentifier - Unique ImportProeject identifier
     * @param string $type - One of self::MEDIA_TYPE_*
     * @param string $title - Media item title
     * @param array $attributes (optional) - Additional Site attributes
     */
    public function __construct($identifier, $projectIdentifier, $type, $title, $attributes = [])
    {
        $this->identifier        = $identifier;
        $this->projectIdentifier = $projectIdentifier;
        $this->type              = $type;
        $this->title             = $title;

        $this->setAttributes($attributes);
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return int|string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isFolder()
    {
        return $this->type === self::MEDIA_TYPE_FOLDER;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return int|null
     */
    public function getQuiqqerMediaId()
    {
        return $this->quiqqerMediaId;
    }

    /**
     * Set a custom QUIQQER media id
     *
     * The import process will try to assign this ID to the QUIQQER media item
     *
     * @param int $quiqqerMediaId
     */
    public function setQuiqqerMediaId($quiqqerMediaId)
    {
        $this->quiqqerMediaId = $quiqqerMediaId;
    }

    /**
     * @return string|int
     */
    public function getProjectIdentifier()
    {
        return $this->projectIdentifier;
    }
}
