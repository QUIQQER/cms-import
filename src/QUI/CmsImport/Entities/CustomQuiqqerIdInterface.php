<?php

namespace QUI\CmsImport\Entities;

interface CustomQuiqqerIdInterface
{
    /**
     * Set QUIQQER ID
     *
     * @param int $id
     * @return void
     */
    public function setQuiqqerId(int $id);

    /**
     * Return QUIQQER ID
     *
     * @return int|null
     */
    public function getQuiqqerId();
}
