<?php

namespace QUI\CmsImport;

abstract class AbstractImportProvider implements ImportProviderInterface
{
    /**
     * Instance of QUI\CmsImport\Import that is orchestrating the current import process
     *
     * @var Import
     */
    protected $Import = null;

    /**
     * The main QUIQQER Import console tool
     *
     * @var Console
     */
    protected $ImportConsole;

    /**
     * ImportProviderInterface constructor.
     *
     * @param Console $ImportConsole - The main QUIQQER Import console tool
     */
    public function __construct(Console $ImportConsole)
    {
        $this->ImportConsole = $ImportConsole;
    }

    /**
     * Write an info msg
     *
     * @param string $msg
     * @return void
     */
    protected function writeInfo($msg)
    {
        $this->ImportConsole->writeInfo($msg);
    }

    /**
     * Write a warning msg
     *
     * @param string $msg
     * @return void
     */
    protected function writeWarning($msg)
    {
        $this->ImportConsole->writeWarning($msg);
    }

    /**
     * Write an error msg
     *
     * @param string $msg
     * @return void
     */
    protected function writeError($msg)
    {
        $this->ImportConsole->writeError($msg);
    }

    /**
     * Output and return a user prompt
     *
     * @param string $msg
     * @param string $defaultValue (optional)
     * @return string
     */
    protected function writePrompt($msg, $defaultValue = null)
    {
        return $this->ImportConsole->writePrompt($msg, $defaultValue);
    }

    /**
     * Write a header
     *
     * @param string $title
     * @return void
     */
    protected function writeHeader($title)
    {
        $this->ImportConsole->writeHeader($title);
    }

    /**
     * Set instance of QUI\CmsImport\Import that is orchestrating the current import process
     *
     * @param Import $Import
     * @return void
     */
    public function setImport(Import $Import)
    {
        $this->Import = $Import;
    }

    /**
     * Get instance of QUI\CmsImport\Import that is orchestrating the current import process
     *
     * @return Import
     */
    public function getImport()
    {
        return $this->Import;
    }
}
