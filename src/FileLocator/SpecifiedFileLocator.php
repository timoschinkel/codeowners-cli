<?php
declare(strict_types=1);

namespace CodeOwners\Cli\FileLocator;

final class SpecifiedFileLocator implements FileLocatorInterface
{
    /** @var string */
    private $file;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * @return string
     * @throws UnableToLocateFileException
     */
    public function locateFile(): string
    {
        if (file_exists($this->file) === false) {
            throw new UnableToLocateFileException("CodeOwners file {$this->file} does not exist");
        }

        return $this->file;
    }
}
