<?php

namespace QUI\CmsImport\Entities;

use QUI;

/**
 * Class ImportProject
 *
 * Represents a QUIQQER project that is imported
 */
class ImportProject extends QUI\QDOM
{
    /**
     * ImportProject constructor.
     *
     * @param string $name
     * @param string $lang - Project standard language
     * @param array $languages (optional) - All available Project languages
     * @param array $attributes (optional) - Additional attributes
     */
    public function __construct($name, $lang, $languages = [], $attributes = [])
    {
        $this->setAttributes(array_merge(
            $attributes,
            [
                'name'      => $name,
                'lang'      => $lang,
                'languages' => $languages
            ]
        ));
    }
}
