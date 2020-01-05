<?php
declare(strict_types=1);

namespace CodeOwners\Cli\FileLocator;

interface FileLocatorInterface
{
    /**
     * @return string
     * @throws UnableToLocateFileException
     */
    public function locateFile(): string;
}
